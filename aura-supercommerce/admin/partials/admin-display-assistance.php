<?php

/**
 * 
 * Assistance screen for All users
 *
 * @since      1.0.0
 *
 * @package           Aura_Supercommerce
 * @subpackage 	      Aura_Supercommerce/includes
 */
?>



<div class="wp-tab-panel aura-super wrap" id="tabs-3" style="display: none;">
	<div class="wrap"> 

		<div class="panel-heading">
			<h2>Tasks</h2>
			<h4>Version: <?php echo $this->version ?></h4>
		</div>

		<div class="tab-content">
			<div class="tab-inner assist-info">

				<?php

				$aura_sc_admin = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );
				$dualeng_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-dual-engine' );
				$agent_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-agent' );
				$conversion_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-conversion' );
				$publicity_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-publicity' );
				$stock_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-stock-maximiser' );
				$time_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-time-saver' );
				$trade_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-trade-booster' );

				?>

				

				<div class="col col-lg-4">
					<h1>Foundation</h1>
					<div class="aura-admin-btn"><a href="<?php echo get_admin_url(); ?>edit.php?post_type=shop_coupon"><i class="fas fa-tags"></i> Create/Edit Coupons</a></div>
					<div class="aura-admin-btn"><a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=email"><i class="fas fa-envelope"></i> Default Notification Emails</a></div>

				</div>

				<?php

				if ($dualeng_plugin_exists):

				?>

				<div class="col col-lg-4">

					<h1>Dual Engine</h1>
					<div class="aura-admin-btn"><a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=products&section=ordervalue"><i class="fas fa-minus-circle"></i> Set Min. Order Amount</a></div>
					<div class="aura-admin-btn"><a href="<?php echo get_admin_url(); ?>edit.php?post_type=product&page=aura_category_overview"><i class="fas fa-clipboard-list"></i> Category Overview</a></div>
					<div class="aura-admin-btn"><a href="<?php echo get_site_url(); ?>/bulk-order"><i class="fas fa-receipt"></i> Bulk Order</a></div>
					<div class="aura-admin-btn"><a href="<?php echo get_admin_url(); ?>edit.php?post_type=wc_membership_plan"><i class="fas fa-users-cog"></i> Create/Edit Member Plans</a></div>
					<div class="aura-admin-btn"><a href="<?php echo get_admin_url(); ?>edit.php?post_type=wc_user_memberships"><i class="fas fa-users"></i> Create/Edit Memberships</a></div>
					
				</div>

				<?php 

				endif; 

				if ($publicity_plugin_exists):

				?>
				
				<div class="col col-lg-4">
					<h1>Publicity</h1>
					<div class="aura-admin-btn"><a href="<?php echo get_site_url(); ?>/publicity"><i class="far fa-newspaper"></i> Publicity Page</a></div>
				</div>


				<?php 

				endif; 

				if ($time_plugin_exists):

				?>

				<div class="col col-lg-4">
					<h1>Time Saver</h1>
					<div class="aura-admin-btn"><a href="<?php echo get_site_url(); ?>/product-barcodes/"><i class="fas fa-barcode"></i> Barcodes Page</a></div>
				</div>

				<?php 

				endif; 

				if ($trade_plugin_exists):

				?>

				<div class="col col-lg-4">
					<h1>Trade Booster</h1>
					<div class="aura-admin-btn"><a href="<?php echo get_site_url(); ?>/shipping-addresses/"><i class="fas fa-dolly"></i> Shipping Addresses</a></div>
					<div class="aura-admin-btn"><a href="<?php echo get_admin_url(); ?>admin.php?page=advanced-notifications/"><i class="fas fa-mail-bulk"></i> Advanced Email Notifications</a></div>
				</div>


				<?php 

				endif;  ?>	

			</div>
		</div>
	</div>
</div>