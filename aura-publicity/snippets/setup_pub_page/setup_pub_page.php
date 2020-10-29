<?php

/**
 * Snippet Name: Setup Publicity Page & Password HTML
 * Version: 1.0.0
 * Description: 
 * Dependency: 
 *
 * @link              https://digitalzest.co.uk
 * @since             1.0.0
 * @package           Aura_Dual_Engine
 *
**/



function setup_pub_page(){
	if( get_page_by_title( 'Publicity' ) == NULL ) {
	    create_pages_fly( 'Publicity' );
	}

}

function create_pages_fly($pageName) {
    $createPage = array(
      'post_title'    => $pageName,
      'post_content'  => '',
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_type'     => 'page',
      'post_name'     => $pageName
    );

    // Insert the post into the database
    wp_insert_post( $createPage );
}

add_action('admin_init', 'setup_pub_page');


function my_password_form() {
    global $post;

    $label = 'pwbox-'.( empty( $post->ID ) ? rand() : $post->ID );

    $o = '<div id="publicity-pass-form" class="publicity-wall-col"><h2>Got a Password?</h2><form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" method="post">
    ' . __( "To view this protected post, enter the password below. If you don't have the password please enter your email below:" ) . '
    <label for="' . $label . '">' . __( "Password:" ) . ' </label><input name="post_password" id="' . $label . '" type="password" size="20" maxlength="20" /><input type="submit" name="Submit" value="' . esc_attr__( "Submit" ) . '" />
    </form></div>
    <div id="publicity-email-form" class="publicity-wall-col">
    <h2>Need Access? Get in Touch</h2><p>If you would like access please provide us with your email and an auto-reposnder will email you the password.</p>Setup Contact Form here</div>';
    return $o;
}
add_filter( 'the_password_form', 'my_password_form' );