<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://digitalzest.co.uk
 * @since      1.0.0
 *
 * @package    Dz_Woo_Tpt
 * @subpackage Dz_Woo_Tpt/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->


<div class="wrap dz-tpt-admin-page">
    <h1>DZ Top Product Types</h1>

    <form method="post" action="options.php">

        <!-- Your settings fields here -->
        <?php       
            settings_fields('dz-tpt-settings-group');
            do_settings_sections('dz-tpt-settings-group');
        ?>

        <table class="form-table">
            <tr>
                <td>
                    <label fror="dz_tpt_url">Webhook URL</label>
                    <input type="text" name="dz_tpt_url" value="<?php echo esc_attr(get_option('dz_tpt_url')); ?>" /></td>
                </td>
            </tr>
        </table>

        <table class="form-table">
  
            <tr valign="top">
                <th scope="row">Product Categories</th>
                <td>
                    <table>

                    <?php
                        // Load the available taxonomies
                        $taxonomies = get_taxonomies([], 'names');

                        $dz_tpt_options = get_option('dz_tpt_options');
                        $tpt_chosen_taxons = isset($dz_tpt_options) ? $dz_tpt_options : '';

                        $select_fields = ['product_cat', 'type', 'occasion', 'range'];

                        foreach ($select_fields as $select) : ?>
                            <tr>
                                <td>
                                    <label for="dz_tpt_options[<?php echo $select; ?>]"><?php echo $select; ?></label>
                                </td>
                                <td>
                                    <select name="dz_tpt_options[<?php echo $select; ?>]">

                                    <option value="" <?php selected($tpt_chosen_taxon, '', true); ?>>None/Unmapped</option> 

                                    <?php 

                                        $tpt_chosen_taxon = isset($dz_tpt_options[$select]) ? $dz_tpt_options[$select] : '';
                                    // $tpt_current_taxon = get_option('dz_tpt_options["' . $select . '"]');

                                        foreach ($taxonomies as $taxonomy) {
                                            
                                            echo '<option value="' . $taxonomy . '"' . selected($tpt_chosen_taxon, $taxonomy) . '>' . $taxonomy . '</option>';
                                        } 

                                    ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </table>
                </td>
            </tr>
       
        </table>
        
        <?php 

  

        submit_button(); 
        
        ?>

    </form>
</div>

