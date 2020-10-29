<?php


/**
 *
 * Snippet Name:      Plugin 1
 * Description:       Leave this for testing
 * Version:           1.0.0
 * Author:            Aura Creative Media
 * Author URI:        https://auracreativemedia.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aura-supercommerce
 * Domain Path:       /languages
 */




	class Aura_Plugin_1 {
		
		public function __construct() {
			$this->plugin_name = 'Plugin 1';
			$this->plugin_slug = 'plugin_1';

			$this->setup_Plugin();

			add_action('admin_menu', array( $this, 'admin_menu_plugin_1' ));

		}


		public function setup_Plugin() {
			
			// echo $this->plugin_name;
		}

		public function admin_menu_plugin_1(){
		    // add_submenu_page( 'aura-supercommerce', 'plugin-1', 'Plugin 1', 'manage_options', 'aura-plugin-1', array( $this, 'plugin_1_admin_page_display' ) );
		}


		public function plugin_1_admin_page_display(){ 
			?>


			<div class="wrap">
				
					<h1 class="wp-heading-inline"><?php _e('Aura SuperCommerce_Plugin_1', $this->plugin_name); ?></h1>

					<div class="row flex-xl-nowrap">
					    <div class="col-12 col-md-8 col-xl-12">
							
							<hr>

					    	test
						
					    </div>
					</div>
				
			</div>

			<?php
				
		}

		
	}





$Plugin_1 = new Aura_Plugin_1();