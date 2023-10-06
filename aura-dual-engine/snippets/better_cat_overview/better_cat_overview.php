<?php

/**
 * Snippet Name: Better Category Overview
 * Version: 1.1.1
 * Description: Displays a better overview of all categories including meta options, useful for admins to debug and maintain their store
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Dual_Engine
 *
**/



  
add_action('admin_menu', 'aura_register_my_custom_submenu_page');
 
function aura_register_my_custom_submenu_page() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Category Overview',
        'Category Overview',
        'manage_options',
        'aura_category_overview',
        'aura_cat_overview_page_callback' );
}


 
function aura_cat_overview_page_callback() {


	if ( is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) || is_plugin_active( 'advanced-custom-fields/acf.php' ) ) {
    
	$categories = get_terms( ['taxonomy' => 'product_cat', 'hide_empty' => false] );

    ?> 
    
    <div class="wrap"><div id="icon-tools" class="icon32"></div>

        <h2>Better Category Overview</h2>
        <br>
        <hr>
        <br>
  
		<table class="widefat" id="better-cat-table">
		<style>	
			#better-cat-table tr:nth-child(even) {
				background: #f1f1f1;
			}
			#better-cat-table td {
				border-right: 1px solid #dedede;
			}
			#better-cat-table td:not(first-child),
			#better-cat-table th:not(first-child) {
				text-align: center;
			}

		</style>
			<thead>
			<tr>
				<th class="row-title"><?php esc_attr_e( 'Category', 'aura-dual-engine' ); ?></th>
				<th><?php esc_attr_e( 'Hidden from Retail?', 'aura-dual-engine' ); ?></th>
				<th><?php esc_attr_e( 'Hidden from Trade?', 'aura-dual-engine' ); ?></th>
				<th><?php esc_attr_e( 'Category Minimum', 'aura-dual-engine' ); ?></th>
				<th><?php esc_attr_e( 'Hidden from "Shop" page', 'aura-dual-engine' ); ?></th>
			</tr>
			</thead>

			<tbody>

			<?php 

			foreach ($categories as $category) { 

				$category_link = '<a href="/wp-admin/term.php?taxonomy=product_cat&tag_ID=' . $category->term_id . '" >' . $category->name . '</a>';

				$display_tick = false;
				$hide_field_retail = "";
				$hide_field_trade = "";
				$fccm_meta_minimum = "";
				$exclude_field_shop = "";
				$fccm_meta_min_trade = "";
				$fccm_meta_min_retail = "";

				if (get_field('hcu_hidden_user_trade', $category)) {
				  $hide_field_trade = get_field('hcu_hidden_user_trade', $category );
				}
				if (get_field('hcu_hidden_user_retail', $category)) {
				  $hide_field_retail = get_field('hcu_hidden_user_retail', $category );
				}
				if (get_field('hcu_not_archive_visible', $category)) {
				  $exclude_field_shop = get_field('hcu_not_archive_visible', $category );
				}
				
				
				$display_tick = '<span style="color: red;" class="dashicons dashicons-yes-alt"></span>'; 
			//	$display_fail = '<span class="dashicons dashicons-dismiss"></span>';
				if (get_field('fccm_meta_minimum', $category)) {
				   $fccm_meta_minimum = get_field('fccm_meta_minimum', $category);
				   $fccm_meta_min_trade = get_field('fccm_meta_min_trade', $category);
				   $fccm_meta_min_retail = get_field('fccm_meta_min_retail', $category);

				
				}
				
			?>

				<tr>
					<td class="row-title"><label for="tablecell"><?php echo $category_link ?></label></td>
					<td><?php if($hide_field_retail) : echo $display_tick; /* else : echo $display_fail; */ endif; ?></td>
					<td><?php if($hide_field_trade) : echo $display_tick; /* else : echo $display_fail; */ endif; ?></td>
					<td>
						<?php if ($fccm_meta_minimum) : ?> Must have <span style="color: red; font-weight: bold;"><?php echo $fccm_meta_minimum; ?></span> of any in this category <?php endif;?> 
						<?php if ($fccm_meta_min_trade) : ?> <br><span class='dashicons dashicons-awards'></span> Applies to <span style="font-weight: bold;">Trade</span><?php endif;?> 
						<?php if ($fccm_meta_min_retail) : ?> <br><span class='dashicons dashicons-groups'></span> Applies to <span style="font-weight: bold;">Retail</span><?php endif;?>  
					</td>
					<td><?php if($exclude_field_shop) : echo $display_tick; /* else : echo $display_fail; */ endif; ?></td>
				</tr>

			<?php	

			}

			?>

			</tbody>
			
		</table>
  
<?php
	}
	else {
		echo '<h2>Please install <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Advanced Custom Fields</a> to enable this feature</h2>';
	}

}

?>