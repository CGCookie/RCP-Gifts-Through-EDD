<?php

class CGC_RCP_Redeem_Gift {

	public function __construct() {

		add_action('cgc_free_signup_form_fields', 	array($this,'add_redeem_field'));
		add_action( 'cgc_free_signup_redeem', 		array( $this, 'process_gift_redemption' ), 10, 2 );
	}

	public function add_redeem_field(){

		$is_citizen = class_exists('cgcUserApi') ? cgcUserApi::is_user_citizen() : false;

		if ( is_page('redeem') && !$is_citizen ): ?>
			<fieldset id="rcp_redeem_gift_code" class="rcp_redemption_fieldset registration-step <?php echo is_user_logged_in() ? 'logged-in' : false;?> ">
				<div class="registration-step-info">
					<p class="rcp_subscription_message">Paste your Gift Membership code below to automatically redeem your free membership to CG Cookie.</p>
				</div>
				<div class="registration-step-controls">
					<?php if( rcp_has_discounts() ) : ?>
						<p id="rcp_discount_code_p_wrap">
							<input type="text" id="rcp_discount_code" name="rcp_discount" class="rcp_discount_code" required placeholder="Enter Gift Code" value=""/>
						</p>
					<?php endif; ?>
				</div>
			</fieldset>
			<input type="hidden" name="page_type" value="redeem">
		<?php endif;
	}

	/**
	*	Process the gift redemption part by hooking into the free signup form
	*
	*	@param $user_id int the id of the user that's been created
	*	@param $code string the discount code that's being entered into the form
	*	@since 6.6
	*/
	public function process_gift_redemption( $user_id, $code ) {

		global $wpdb;

		if ( empty( $user_id ) || empty( $code ) || !class_exists( 'RCP_Discounts' ) )
			return;

		$error = false;

		$discounts = new RCP_Discounts();
		$discount  = $discounts->get_by( 'code', $code );

		// Validate this as a proper gift
		$payment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id from $wpdb->postmeta WHERE meta_key = '_edd_rcp_gift_id' AND meta_value = '%d';", $discount->id ) );

		// Find the subscription level this discount gives access to
		$subscription = $discount->subscription_id;
		$expiration   = rcp_calculate_subscription_expiration( $subscription );
		$sub_details  = rcp_get_subscription_details( $subscription );



		if( ! empty( $sub_details->duration ) && '0' == $sub_details->price ) {

			// This is a trial subscription level
			if( rcp_has_used_trial( $user_id ) ) {

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
			$error_code = '6';
		}

		if( ! $error ) {

			// all good
			$discounts->increase_uses( $discount->id );
			$discounts->add_to_user( $user_id, $code );

			// Check if the code being redeemed is for a trial subscription
			if( ! empty( $sub_details->duration ) && '0' == $sub_details->price ) {
				update_user_meta( $user_id, 'rcp_is_trialing', 'yes' );
				update_user_meta( $user_id, 'rcp_has_trialed', 'yes' );
			}

			update_user_meta( $user_id, 'rcp_subscription_level', $subscription );
			update_user_meta( $user_id, 'rcp_expiration', $expiration );
			rcp_set_status( $user_id, 'active' );

			do_action( 'cgc_gift_redeemed', $user_id, $discount, $sub_details );

			//wp_redirect( home_url( 'redeem-thanks' ) );

		}

	}

}
