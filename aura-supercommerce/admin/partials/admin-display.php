<?php

/**
 * The main settings content for our beautiful plugin..
 *
 * This file is used to setup the main settings area
 *
 * @since      1.0.0
 *
 * @package           Aura_Supercommerce
 * @subpackage 	      Aura_Supercommerce/includes
 */
?>

<div id="aura-body" class="wrap">
	<div class="block-heading">
		<img src="<?php echo plugins_url() . '/aura-supercommerce/admin/partials/images/dz-logo.png' ?>" alt="">
		<h1 class="wp-heading-inline"><?php _e('Creative Commerce', $this->plugin_name); ?></h1>
	</div>
		

		<div class="row flex-xl-nowrap">
		    <div class="col-12 col-md-8 col-xl-12">

		    	<?php 

		    	echo $this->admin_display_tabs(); 

		    	 ?>

			
		    </div>
		</div>
	
</div>
	
<!-- /public_html/wp-content/plugins/aura-supercommerce/admin/partials/images -->