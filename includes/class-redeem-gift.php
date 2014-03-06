<?php

class CGC_RCP_Redeem_Gift {
	
	public function __construct() {
		add_action( 'init', array( $this, 'process_gift_redemption' ) );
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
		if( ! $payment_id )  {

			$error = true;
			$error_code = '1';

		}

		if( absint( $discount->use_count ) >= 1 ) {

			$error = true;
			$error_code = '2';
		}

		if( ! $error ) {

			// all good
			$discounts->increase_uses( $discount->id );
			$discounts->add_to_user( get_current_user_id(), $code );

			// Find the subscription level this discount gives access to
			$subscription = $discount->subscription_id;
			$expiration   = rcp_calculate_subscription_expiration( $subscription );

			update_user_meta( get_current_user_id(), 'rcp_subscription_level', $subscription );
			update_user_meta( get_current_user_id(), 'rcp_expiration', $expiration );

			wp_redirect( home_url( 'redeem-thanks' ) ); exit;

		} else {

			wp_redirect( add_query_arg( 'error', $error_code ) ); exit;

		}

	}

}