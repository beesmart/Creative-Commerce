/* Show Quantity Field on Archive Pages (Shop) */


(function($)
{ 
    
    $( ".post-type-archive-product" ).on( "click", ".quantity input", function() {
        return false;
    });
    $( ".post-type-archive-product" ).on( "change input", ".quantity .qty", function() {
        var add_to_cart_button = jQuery( this ).parents( ".product" ).find( ".add_to_cart_button" );
        // For AJAX add-to-cart actions
        add_to_cart_button.attr( "data-quantity", jQuery( this ).val() );
        // For non-AJAX add-to-cart actions
        add_to_cart_button.attr( "href", "?add-to-cart=" + add_to_cart_button.attr( "data-product_id" ) + "&quantity=" + jQuery( this ).val() );
    });


})( jQuery );


	




