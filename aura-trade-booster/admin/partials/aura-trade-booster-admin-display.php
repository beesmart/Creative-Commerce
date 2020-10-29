<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://auracreativemedia.co.uk/
 * @since      1.0.0
 *
 * @package    Aura_Trade_Booster
 * @subpackage Aura_Trade_Booster/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->



<div id="aura-body" class="wrap">
	
	<div class="block-heading">
		<img src="<?php echo plugins_url() . '/aura-supercommerce/admin/partials/images/acm-logo.png' ?>" alt="">
		<h1 class="wp-heading-inline"><?php _e('Creative Commerce', $this->plugin_name); ?></h1>
	</div>
		

		<div class="row flex-xl-nowrap">
		    <div class="col-12 col-md-8 col-xl-12">
				<div class="wp-tab-panel aura-super wrap" id="tabs-1">
					<div class="wrap">
						<div class="panel-heading">
							<h2>Snippets Dashboard</h2>
							<h4>Version: <?php echo $this->version ?></h4>
						</div>
						<div class="tab-content">
							<div class="tab-inner">

				<?php
					echo $this->admin_display_content(); 

				?>
							</div>
						</div>
					</div>
				</div>
		    </div>
		</div>
	
</div>