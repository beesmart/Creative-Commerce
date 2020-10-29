<?php

/**
 * The main settings content for our beautiful plugin..
 *
 * Debug screen for priv Admins only
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
		

		<div class="row flex-xl-nowrap">
		    <div class="col-12 col-md-8 col-xl-12">

		    	<div style="background: black; color: white;">
	    				<h1 style="color: white">Licence Remote Response Data</h1>
	    				<pre>
	    		    	<?php 

	    		    		$licence_key = get_option('aura_licence_key');

	    		    		$aura_licence_checker = new aura_licence_checker;
	    		    		$licence_data = $aura_licence_checker->check_licence_data();

	    		    		print_r($licence_data);
	    		    	?>
	    				</pre>
	    				<hr>
	    				<h1 style="color: white">Transient Licence Data</h1>

	    				<pre>
	    				<?php echo get_option( 'aura_licence_key' ) ?>
	    				</pre>
	    				<pre>
	    				<?php print_r(get_transient( 'licence_transient_data' )) ?>
	    				</pre>
	    			</div>

	    			<div style="background: #121252; color: white;">
	    				<h1 style="color: white">Snippet Data</h1>
	    				<pre>
	    				<?php print_r(get_option( 'aura-supercommerce_snippets' )) ?>
	    				</pre>
	    			</div>

	    			
	    	    </div>

			
		    </div>
		</div>
	
</div>

<div class="row flex-xl-nowrap">
    <div class="col-12 col-md-8 col-xl-12">
		
		<hr>

		
</div>