<?php
/**
 * The main settings content for our beautiful plugin..
 *
 * Status screen for Admins only, helps users debug themselves
 *
 * @since      1.0.0
 *
 * @package           Aura_Supercommerce
 * @subpackage 	      Aura_Supercommerce/includes
 */
?>
<div id="aura-body" class="wrap">
	<div class="block-heading">
		<img src="<?php echo plugins_url() . '/aura-supercommerce/admin/partials/images/acm-logo.png' ?>" alt="">
		<h1 class="wp-heading-inline"><?php _e('Creative Commerce', $this->plugin_name); ?></h1>
	</div>
	<hr style="border: 0;
    border-top: 1px solid #ddd;
    border-bottom: 2px solid #0173b2;
    background: none;
    margin: 0;">
		<div class="row flex-xl-nowrap">
		    <div class="col-12 col-md-8 col-xl-12">
		    	<div class="wp-tab-panel aura-super wrap">
		    		<div class="wrap">
		    			<div class="panel-heading">
		    				<h2>Status</h2>
		    				<h4>Version: <?php echo $this->version ?></h4>
		    			</div>
		    			<div class="tab-content status-rows">
		    				<div class="tab-inner">
		    						<?php 
		    						$aura_sc_admin = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );
		    						$dualeng_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-dual-engine' );
		    						$agent_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-agent' );
		    						$conversion_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-conversion' );
		    						$publicity_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-publicity' );
		    						$stock_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-stock-maximiser' );
		    						$time_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-time-saver' );
		    						$trade_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-trade-booster' );
		    						$is_admin = $aura_sc_admin->check_current_is_privileged_user();
								    $trade_status = $aura_sc_admin->get_trade_status();
		    						?>
		    						<?php 
		    						if ($is_admin) : ?>
		    						<h1>Foundation</h1>
									<?php if($trade_status === "TRUE" && $trade_status != 'FALSE') : echo 'Trade Status: Trade Only'; else : echo 'Trade Status: Dual'; endif;  ?>
		    						<h2>Required Dependencies</h2>
		    						<?php
		    							$this->check_plugin_dependencies();
		    						?>
		    						<?php
		    						endif;
		    						if ($dualeng_plugin_exists) :
		    						?>
		    							<hr style="margin: 20px 0;">
		    							<h1>Dual Engine</h1>
		    							<h2>Products - Visbility Issues</h2>
		    							<h3>Pack Attributes Exist?</h3>
		    							<?php $taxonomy_exist = $this::status_req_attr_exist(); ?>
		    							<div class="postbox <?php if ($taxonomy_exist) : echo 'status-pass'; else: echo 'status-fail'; endif; ?>">
			    						<?php 
			    						if ($taxonomy_exist) {
			    							echo __( '<p style="color: green;">Good! Attribute "Pack Size" is Setup</p>', 'aura-supercommerce' );
			    							$term_single = term_exists( 'single-item', 'pa_pack-size' );
			    							if ( $term_single !== 0 && $term_single !== null ) {
			    							   	   echo __( '<p style="color: green;">Good! Single Pack is Setup!</p>', 'aura-supercommerce' );
			    							} else { echo __( '<p>Warning! Single Pack is NOT Setup!</p>', 'aura-supercommerce' ); }
			    							$term_multi = term_exists( 'multi-pack', 'pa_pack-size' );
			    							if ( $term_multi !== 0 && $term_single !== null ) {
			    							   	   echo __( '<p style="color: green;">Good! Multi Pack is Setup!</p>', 'aura-supercommerce' );
			    							} else { echo __( '<p>Warning! Multi Pack is NOT Setup!</p>', 'aura-supercommerce' ); }
			    						} else {
			    							echo __( '<p>Warning! Please create an Attribute called "Pack Size", with 2 terms, Single Pack and Multi Pack</p>', 'aura-supercommerce' );
			    						}
		    							?>
		    							</div>

		    							<h3>Tax Exclusion Settings</h3>

		    							<?php $exclusion_option = $this::exclude_tax_options_check();

		    							  ?>
		 	    						<div class="postbox <?php if (!$exclusion_option) : echo 'status-fail'; else: echo 'status-pass'; endif; ?>">
		 	    						<?php
		 	    							if (!$exclusion_option) :
		 	    								echo '<p>Warning: There are no tax exclusion options setup for WooCommerce / Memberships. Please confirm this is correct <a href="' . get_site_url() . "/wp-admin/admin.php?page=wc-settings&tab=tax" . '">here.</a></p>';
		 	    							else :
		 	    								echo '<p>Great, there is a Tax exclusion setup for at least one member plan.</p>';
		 	    							endif;
		 	    
		 	    						?>

		     							</div>

		    							<h3>Unassigned Attributes</h3>
		    							<?php // query all products to see if they are inside a bundle. If so check they have the correct attributes Pack Size. and single. If not echo. Also show bundles which dont have the attribute. 
			    						$warning_products = $this::status_unassigned_attr_exist();
			    						?>
			    						<div class="postbox <?php if (!$warning_products) : echo 'status-pass'; else: echo 'status-fail'; endif; ?>">
			    						<?php
			    							if ($warning_products) :
			    								echo '<p>Warning there are products which may require the Single Pack Attribute. See here for more info...</p>';
			    								echo '<ul>'; 
			    								foreach ($warning_products as $value) {
			    									echo '<li ><a href="' . get_edit_post_link( $value ) . '" style="color: red">' . get_the_title( $value ) . '</a></li>';
			    								}
			    								echo '</ul>'; 
			    						endif;
			    						//check these have attributes
			    						?>
		    							</div>
		    							<hr>
		    						<h2>Products - Price Issues</h2>
		    						<h3>Membership Price Tiers Applied to All Products</h3>
		    						<?php 
		    						$mem_plans = $this::global_membership_price_tier_exists(); ?>
		    						<div class="postbox <?php if (!$mem_plans) : echo 'status-pass'; else: echo 'status-fail'; endif; ?>">
		    						<?php
		    							if ($mem_plans) :
		    								echo 'The following plans may have globally active price brackets: ';
		    								foreach ($mem_plans as $value) {
		    									echo '<li><a href="' . get_edit_post_link( $value['membership_plan_id'] ) . '">' . get_the_title( $value['membership_plan_id'] ) . ' - #' . $value['membership_plan_id'] . '</a></li>';
		    								}
		    							else : echo '<p>No globally active price brackets found</p>'; 
		    							endif;
		    							?>
		    						</div>
		    						<hr>
		    						<h2>Products - Other Issues</h2>
		    						<h3>Missing Images</h3>
		    						<?php
		    							$my_posts = $this::missing_product_images_exist(); ?>
		    					    <div class="postbox <?php if (!$my_posts) : echo 'status-pass'; else: echo 'status-fail'; endif; ?>">
		    						<?php
		    							echo '<ul>';
										    if($my_posts) :
                                            if($my_posts->have_posts()) :
		    									while ( $my_posts->have_posts() ) :
		    										  $my_posts->the_post(); 
		    										  echo '<li><a href="' . get_edit_post_link() . '">' . get_the_title() . '</a></li>';
		    									endwhile;
										   endif;
										endif;
		    								echo '</ul>';
		    							?>
		    						</div>
		    						<h2>Users - Member Issues</h2>
		    						<h3>Trade Users With No Memberships</h3>
		    						<?php
		    							$mem_data = $this::tradecust_no_attached_membership(); ?>
		    						<div class="postbox <?php if (!$mem_data) : echo 'status-pass'; else: echo 'status-fail'; endif; ?>">
		    						<?php
		    						if ($mem_data) :
	    								foreach ($mem_data as $value) {
	    									if($value) : 
	    										echo '<p>Warning! <a href="' . get_edit_user_link($value) . '"> Trade Customer: ' . get_userdata($value)->user_login . ' does not appear to have a membership attached</a></p>'; 
	    									endif;
	    								}
	    							endif; ?>
		    						</div>
		    					<?php endif; 	?>
		    					<hr style="margin: 20px 0;">
		    					<h1>Agents</h1>
		    					<h2>Agents - Potential Issues</h2>
		    					<h3>Trade Users with no attached Agents</h3>
		    					<div class="postbox">
		    					<?php 
		    					if ($agent_plugin_exists) :
			    					$agent_data = $this::tradecust_no_attached_agents(); 
			    					if ($agent_data) : ?>
			    						<ul>
				    					<?php foreach ($agent_data as $value) {
				    						$user = get_user_by('id', $value);
				    						echo "<li><a href='" . get_site_url() . "/wp-admin/user-edit.php?user_id=" . $value . "' target='_Blank'>" . $user->user_nicename . "</li>";
				    					}  ?>
				    					</ul>
				    					<?php
			    					endif;
		    					endif; 
		    					?>
		    					</div>
		    					<?php
		    					if ($agent_plugin_exists) : ?>
		    					<h3>Agents with no attached Trade Users</h3>
		    					<div class="postbox">
			    				<?php	$agent_data_users = $this::agents_no_attached_customers(); 
									if ($agent_data) : ?>
			    						<ul>
				    					<?php foreach ($agent_data_users as $value) {
				    						$user = get_user_by('id', $value);
				    						echo "<li><a href='" . get_site_url() . "/wp-admin/user-edit.php?user_id=" . $value . "' target='_Blank'>" . $user->user_nicename . "</a></li>";
				    					} ?>
				    					</ul>
				    					<?php
			    					endif; ?>
			    					</div>
		    					<?php endif; 
		    					?>
		    					<hr style="margin: 20px 0;">
		    					<h1>Trade Booster</h1>
		    					<h2>Stockists - Potential Issues</h2>
		    					<h3>Monthly reports not setup</h3>
		    					<?php if ($trade_plugin_exists) : 
		    						$wpsl_mailto_report = get_option( 'wpsl_auto_report_mailto' );
		    					?>
		    					<div class="postbox <?php if (!empty($wpsl_mailto_report)) : echo 'status-pass'; else: echo 'status-fail'; endif; ?>">
		    						<?php if (!empty($wpsl_mailto_report)) : ?>
		    							<p>Great! You have an email filled out</p>
		    						<?php else : ?>
		    							<p>Please fill your email here: <a href="<?php echo get_admin_url(); ?>edit.php?post_type=wpsl_stores&page=wpsl_report"><i class="fas fa-mail-bulk"></i> Setup Monthly Stockist Reports</a></p>
		    						<?php endif; ?>
		    					<?php endif ?>
		    					</div>
		    				</div>
		    			</div>
		    		</div>
		    	</div>
		    </div>
		</div>
</div>