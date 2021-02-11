<?php




	$active_plugins = $this::list_active_plugins();
	$aura_sc_admin = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );
	$aura_licence_checker  = new aura_licence_checker;
	$products = $aura_licence_checker->check_licence_products();

	$trade_status = $aura_sc_admin->get_trade_status();

?>


<div id="tabs-2" class="aura-super wrap wp-tab-panel" <?php 
				if ($show_admin_screens) : echo 'style="display: none;"'; endif; ?> >

	<div class="wrap">
		<div class="panel-heading">
			<h2>Products Dashboard</h2>
			<h4>Version: <?php echo $this->version ?></h4>
		</div>

	<?php
		if ($trade_status) :
	?>
		<div class="cc-notice-box">
			<h2>Creative Commerce is set to <strong>Trade Only</strong>. Some features of the Dual Engine plugin have been disabled. You can re-enable by upgrading, but if you believe this is an error please let us know.</h2>
		</div>
	<?php endif; ?>

	<div class="tab-content">
		<div class="tab-inner">
			<div class="tab-inner-subtitle">
				<h4>You will find all information on your <span class="active-green">activated</span> and <span class="live-orange">licensed</span> products here.</h4>
			</div>
		
		

<?php

foreach (AURA_SUPERCOMMERCE_PLUGINS as $value) {

	$current_is_activated = false;

	foreach ($active_plugins as $plugin) {

		if ($plugin->title === $value['slug']) : 
		
			$version = $plugin->version;
			$current_is_activated = true;

		endif;
	}

	$is_licenced = $aura_licence_checker->compare_licence_products($value['slug']);

	?>
	

		<div class="col col-lg-12 col-md-12 col-sm-12">
			<div class="col__inner">
				<div class="plugin-pnl <?php if ( !$is_licenced || !$current_is_activated ) : echo 'no-licence'; endif; ?>">
					<div class="plugin-pnl-border">
					<div class="plugin-pnl__row">
						<div class="plugin-pnl__bg" style="background-image: url('<?php echo $value['image_URL']; ?>') ; ">
						</div>
						<div class="plugin-pnl__title">
							<h2><?php echo $value['title']; ?> <span class="version-title">Version: <?php echo $version; ?></span></h2>
							
						</div>
						<div class="plugin-pnl__status">
							<span class="pnl__status-title">Status: </span>
							<?php 

							if($current_is_activated) : echo '<div class="text__status active"><span>Activated</span></div>'; else: echo '<div class="text__status inactive"><span>Not Activated</span></div>'; endif;
							if($is_licenced) : echo '<div class="text__status licence"><span>Licenced</span></div>'; else: echo '<div class="text__status unlicenced"><span>Unlicenced</span></div>'; endif;

							$my_current_screen = get_current_screen();
							$my_current_screen = $my_current_screen->parent_file;

							$options_url = admin_url( 'admin.php?page=' . $value['slug'] );

							if( $current_is_activated && ($my_current_screen != $value['slug']) && current_user_can( 'manage_debug' ) ) :
								?>

								<a href="<?php echo $options_url;  ?>"><div class="text__status options"><span>Manage Options</span></div></a>

							<?php

							endif;

							?>

						</div>
					</div>
					<div class="plugin-pnl__row">
						<div class="plugin-pnl__features">
							<h2>Features</h2>			
							<?php echo $this::get_plugin_text('features', $value['slug']); ?>
						</div>
						<div class="plugin-pnl__text">

							<h2>Description</h2>
							<?php echo $this::get_plugin_text('details', $value['slug']); ?>
						</div>
					</div>
				</div>
				</div>
			</div>
		</div>	
		

	<?php } ?>
		</div>
	</div>	
	</div>
</div>