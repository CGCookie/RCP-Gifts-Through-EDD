<?php

class CGC_RCP_Redeem_Gift {
	
	public function __construct() {
		add_action( 'init', array( $this, 'process_gift_redemption' ), 9 );
	}

	public function process_gift_redemption() {

		global $wpdb;

		if( empty( $_POST['rcp_submit_redemption'] ) ) {
			return;
		}

		if( empty( $_POST['rcp_discount'] ) ) {
			return;
		}

		if( ! is_user_logged_in() ) {
			return;
		}

		if( ! class_exists( 'RCP_Discounts' ) ) {
			return;
		}

		$error = false;

		$code      = sanitize_text_field( $_POST['rcp_discount'] );

		$discounts = new RCP_Discounts();
		$discount  = $discounts->get_by( 'code', $code );

		// Validate this as a proper gift
		$payment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id from $wpdb->postmeta WHERE meta_key = '_edd_rcp_gift_id' AND meta_value = '%d';", $discount->id ) );
		
		if( ! empty( $sub_details->duration ) && '0' == $sub_details->price ) {

			// This is a trial subscription level
			if( rcp_has_used_trial( get_current_user_id() ) ) {

				$error = true;
				$error_code = '4';

			}

		} elseif( absint( $discount->use_count ) >= 1 && $payment_id ) {

			// This is a gift, so it can only be used once

			$error = true;
			$error_code = '2';
		}

		// Strictly compare discount codes
		if( strcmp( $code, $discount->code ) != 0 ) {
			$error = true;
			$error_code = '5';
		}

		if( empty( $discount->subscription_id ) ) {

			$error = true;
			$error_code = '3';
		}

		if( $discounts->is_expired( $discount->id ) ) {

			$error = true;
			$error_code = '36';
		}

		if( ! $error ) {

			// all good
			$discounts->increase_uses( $discount->id );
			$discounts->add_to_user( get_current_user_id(), $code );

			// Find the subscription level this discount gives access to
			$subscription = $discount->subscription_id;
			$expiration   = rcp_calculate_subscription_expiration( $subscription );
			$sub_details  = rcp_get_subscription_details( $subscription );

			// Check if the code being redeemed is for a trial subscription
			if( ! empty( $sub_details->duration ) && '0' == $sub_details->price ) {
				update_user_meta( get_current_user_id(), 'rcp_is_trialing', 'yes' );
				update_user_meta( get_current_user_id(), 'rcp_has_trialed', 'yes' );
			}

			update_user_meta( get_current_user_id(), 'rcp_subscription_level', $subscription );
			update_user_meta( get_current_user_id(), 'rcp_expiration', $expiration );
			rcp_set_status( get_current_user_id(), 'active' );

			do_action( 'cgc_gift_redeemed', get_current_user_id(), $discount, $sub_details );

			wp_redirect( home_url( 'redeem-thanks' ) ); exit;

		} else {

			wp_redirect( add_query_arg( 'error', $error_code ) ); exit;

		}

	}

}