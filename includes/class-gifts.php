<?php

class RCP_Gift_Products {

	public function __construct(){
		add_action('admin_menu', array($this,'menu_page'),20);
	}

	public function menu_page(){
		add_submenu_page( 'rcp-members', __( 'Gifts', 'rcp' ), __( 'Gifts', 'rcp' ),'manage_options', 'rcp-gifts', array($this,'draw_page') );
	}

	public function draw_page(){

		global $wpdb;
		$page = admin_url( '/admin.php?page=rcp-gifts' );

		?>
		<div class="wrap">
			<?php

				// get all discounts ids
				$getdiscountids = $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_edd_rcp_gift_id';");
				$discount_ids 	= implode( ',',$getdiscountids);
				$discounts 		= $wpdb->get_results( "SELECT * FROM rcp_discounts WHERE id IN(".$discount_ids.");");


			?>
			<h2><?php _e( 'RCP Gifts', 'rcp' ); ?></h2>

			<table class="wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th class="rcp-discounts-id-col"><?php _e( 'ID', 'rcp' ); ?></th>
						<th class="rcp-discounts-name-col" ><?php _e( 'Name', 'rcp' ); ?></th>
						<th class="rcp-discounts-desc-col"><?php _e( 'Description', 'rcp' ); ?></th>
						<th class="rcp-discounts-code-col" ><?php _e( 'Code', 'rcp' ); ?></th>
						<th class="rcp-discounts-subscription-col" ><?php _e( 'Subscription', 'rcp' ); ?></th>
						<th class="rcp-discounts-amount-col"><?php _e( 'Amount', 'rcp' ); ?></th>
						<th class="rcp-discounts-type-col"><?php _e( 'Type', 'rcp' ); ?></th>
						<th class="rcp-discounts-status-col"><?php _e( 'Status', 'rcp' ); ?></th>
						<th class="rcp-discounts-uses-col"><?php _e( 'Uses', 'rcp' ); ?></th>
						<th class="rcp-discounts-uses-left-col"><?php _e( 'Uses Left', 'rcp' ); ?></th>
						<th class="rcp-discounts-expir-col" ><?php _e( 'Expiration', 'rcp' ); ?></th>
						<th class="rcp-discounts-actions-col" ><?php _e( 'Actions', 'rcp' ); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th><?php _e( 'ID', 'rcp' ); ?></th>
						<th><?php _e( 'Name', 'rcp' ); ?></th>
						<th><?php _e( 'Description', 'rcp' ); ?></th>
						<th><?php _e( 'Code', 'rcp' ); ?></th>
						<th><?php _e( 'Subscription', 'rcp' ); ?></th>
						<th><?php _e( 'Amount', 'rcp' ); ?></th>
						<th><?php _e( 'Type', 'rcp' ); ?></th>
						<th><?php _e( 'Status', 'rcp' ); ?></th>
						<th><?php _e( 'Uses', 'rcp' ); ?></th>
						<th><?php _e( 'Uses Left', 'rcp' ); ?></th>
						<th><?php _e( 'Expiration', 'rcp' ); ?></th>
						<th><?php _e( 'Actions', 'rcp' ); ?></th>
					</tr>
				</tfoot>
				<tbody>

				<?php
				if($discounts) :
					$i = 1;
					foreach( $discounts as $key => $discount) : ?>
						<tr class="rcp_row <?php if( rcp_is_odd( $i ) ) { echo 'alternate'; } ?>">
							<td><?php echo $discount->id; ?></td>
							<td><?php echo stripslashes( $discount->name ); ?></td>
							<td><?php echo stripslashes( $discount->description ); ?></td>
							<td><?php echo $discount->code; ?></td>
							<td>
								<?php
								if ( $discount->subscription_id > 0 ) {
									echo rcp_get_subscription_name( $discount->subscription_id );
								} else {
									echo __( 'All Levels', 'rcp' );
								}
								?>
							</td>
							<td><?php echo rcp_discount_sign_filter( $discount->amount, $discount->unit ); ?></td>
							<td><?php echo $discount->unit == '%' ? __( 'Percentage', 'rcp' ) : __( 'Flat', 'rcp' ); ?></td>
							<td>
								<?php
									if(rcp_is_discount_not_expired( $discount->id ) ) {
										echo rcp_get_discount_status( $discount->id ) == 'active' ? __( 'active', 'rcp' ) : __( 'disabled', 'rcp' );
									} else {
										_e( 'expired', 'rcp' );
									}
								?>
							</td>
							<td><?php if( $discount->max_uses > 0 ) { echo rcp_count_discount_code_uses( $discount->code ) . '/' . $discount->max_uses; } else { echo rcp_count_discount_code_uses( $discount->code ); }?></td>
							<td><?php echo rcp_discount_has_uses_left( $discount->id ) ? 'yes' : 'no'; ?></td>
							<td><?php echo $discount->expiration == '' ? __( 'none', 'rcp' ) : date_i18n( 'Y-m-d', strtotime( $discount->expiration ) ); ?></td>
							<?php do_action('rcp_discounts_page_table_column', $discount->id); ?>
							<td>
								<?php if(rcp_get_discount_status($discount->id) == 'active') { ?>
									<a href="<?php echo add_query_arg( 'deactivate_discount', $discount->id, $page ); ?>"><?php _e( 'Deactivate', 'rcp' ); ?></a> |
								<?php } else { ?>
									<a href="<?php echo add_query_arg( 'activate_discount', $discount->id, $page ); ?>"><?php _e( 'Activate', 'rcp' ); ?></a> |
								<?php } ?>
								<a href="<?php echo add_query_arg( 'delete_discount', $discount->id, $page ); ?>" class="rcp_delete_discount"><?php _e( 'Delete', 'rcp' ); ?></a>
							</td>
						</tr>
					<?php
					$i++;
					endforeach;
				else : ?>
				<tr><td colspan="11"><?php _e( 'No discount codes added yet.', 'rcp' ); ?></td>
				<?php endif; ?>
			</table>
		</div>
	<?php
	}
}