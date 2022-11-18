<?php

/**
 * The licence-specific functionality of the plugin.
 *
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @subpackage        Aura_Supercommerce/includes/licence
 */


if( !class_exists( 'aura_licence_checker' ) ) {

    // load our custom updater
    include('class-plugin-licence.php' );
}


/**
 * Add an admin for licence key activation and deactivation
*/


add_action('admin_menu', 'aura_super_licence_page', 12);

function aura_super_licence_page() {
    add_submenu_page('aura-supercommerce', 'Aura SuperCommerce licence', 'Licence', 'manage_options', 'aura_supercommerce_licence', 'aura_licence_management_page');
}

/**
 * Add some HTML Output to display the Licence status
 */

function aura_licence_licence_display() {

    $aura_licence_checker_class = new aura_licence_checker;

    return sprintf("<div class='aura-licence status'>%s</div>", $aura_licence_checker_class->check_has_licence());

}

/**
 * Render the Licence Key submission form and allow activation/deactivtion of the licence on the admin licence page
 */


function aura_licence_management_page() {

    ?>

    <div id="aura-body" class="wrap">

        <div class="block-heading">
            <img src="<?php echo plugins_url() . '/aura-supercommerce/admin/partials/images/acm-logo.png' ?>" alt="">
            <h1 class="wp-heading-inline"><?php _e('Creative Commerce'); ?></h1>
        </div>


            <div class="row flex-xl-nowrap">
                <div class="col-12 col-md-8 col-xl-12">
                    <div class="wp-tab-panel aura-super wrap">
                        <div class="wrap"> 

                            <div class="panel-heading">

                                <h2>Licence Dashboard</h2>
                                <h4>&nbsp;</h4>

                            </div>

                            <div class="tab-content">
                                <div class="tab-inner key-info">
                            <h4>Please enter the licence key for this product to activate it. You should have a record of this licence key, if not please <a href="mailto:hello@digitalzest.co.uk">get in touch.</a></h4>
                            <h3>Without a licence key you will be unable to recieve essential updates.</h3>
                            &nbsp;
                            <hr>
                            &nbsp;
                            <?php

                        
                            $aura_licence_checker_class = new aura_licence_checker;
                            /*** licence activate button was clicked ***/
                            $aura_licence_checker_class->activate_licence();
                            /*** End of licence activation ***/
                            $aura_licence_checker_class->deactivate_licence();

                            ?>

                            <form class="aura-form basic" action="" method="post">
                                <table class="form-table">
                                    <tr>
                                        <th style="width:100px;"><label for="aura_licence_key">Licence Key</label></th>
                                        <td ><input class="regular-text" type="text" id="aura_licence_key" name="aura_licence_key"  value="<?php echo get_option('aura_licence_key'); ?>" ></td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <input type="submit" name="activate_licence" value="Activate" class="button-primary" />
                                    <input type="submit" name="deactivate_licence" value="Deactivate" class="button button-off" />
                                </p>

                            </form>

                            &nbsp;

                            <hr>

                            &nbsp;

                            <div class="key-status"><span class="key-title">Licence Key Status:</span> <?php echo aura_licence_licence_display();?>  
                            </div>

                            </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

    </div>

<?php 

}