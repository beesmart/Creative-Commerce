<?php

/**
 * Snippet Name: Shortcode - Show this products Taxonomy terms
 * Version: 1.0.1
 * Description: Use the shortcode [show-terms tax="sometaxonomy"], to show the taxonomy terms on a single-product.php template.
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.3.15
 * @package           Aura_Supercommerce
 *
**/

function show_thisProducts_terms( $atts ) {
    
    // Load in the default option
    $a = shortcode_atts( array(
        'tax' => 'range'
    ), $atts );

    $post_id = get_the_ID();
    $terms = get_the_terms($post_id, $a['tax']);
    
    if($terms) :

        $term_count = count($terms);
        $i = 0;
        $term_string = "";
        
        foreach($terms as $term){
            $i++;
            $term_link = get_term_link($term->term_taxonomy_id, $a['tax']);
            $term_name = $term->name;
            
            if (count($terms) > 1 && count($terms) > $i) :  $term_punct = ",&nbsp;"; else : $term_punct = ""; endif;
        
            $term_string .= '<li><a href="' . $term_link . '">' . $term_name . '</a>' . $term_punct . '</li>';
        } 
        
        $term_html = '<ul class="show-tax-terms"><span>' . $a['tax'] . ':</span> ' . $term_string . '</ul>';
        return $term_html;
    else :
        return false;    
    endif;
    
}

add_shortcode( 'show-terms', 'show_thisProducts_terms' );