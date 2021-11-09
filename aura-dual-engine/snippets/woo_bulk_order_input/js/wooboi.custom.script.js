jQuery(document).ready(function(){
    
    if( jQuery("select.wooboi-chzn-select").length ){
        
        jQuery(".wooboi-chzn-select").chosen();
        
    }
        
    jQuery('.wooboi_add_more_link').click(function(){

        var data = jQuery('textarea[name="wooboi_add_more_textarea"]').val();

        jQuery('.t-row.wooboi_add_more_row').before(data);

        jQuery(".wooboi-chzn-select").chosen();

    });

    jQuery(document).on('click', 'a.wooboi_remove_rows', function(e) {

        jQuery(this).closest('.t-row').remove();
        wppboi_set_all_products_data();

    });

    jQuery(document).on('change', 'select[name="wooboi_product_options[]"]', function(e) {

        wppboi_set_all_products_data();

    });

    jQuery(document).on('keyup mouseup', 'input[name="wooboi_product_quantity[]"]', function(e) {

        wppboi_set_all_products_data();

    });

    jQuery('.wooboi_add_cart_button button').click(function(){

        var is_error = true;
        jQuery('.wooboi_table_container').find('select[name="wooboi_product_options[]"]').each(function(){

            if( jQuery(this).val() != '' ){
                is_error = false;
            }

        });

        if( is_error ){
            alert('Please select at least one product.'); return false;
        }

    });

});

function wppboi_set_all_products_data(){

    var total = 0;

   jQuery('.wooboi_table_container').find('select[name="wooboi_product_options[]"]').each(function(){

        var closesttr = jQuery(this).closest('.t-row');

        var provalues = closesttr.find('select').val();
        var quantiry = closesttr.find('input[type="number"]').val();

       if(quantiry == '' || quantiry == '0' ){
	   // This line below is an issue for mobiles since they can't remove the one it breaks functionality
       // I suspect this an nesscity in the old version of Nands code where he had to force people into
       // certain set values - increments - wheras the adoption of product bundles has negated the need
    
        //closesttr.find('input[type="number"]').val(1)
        quantiry = 1;
       }

        var amt = 0;
        if( provalues != '' && quantiry != '' ){

            var ret = provalues.split("_");
            amt = ret[1];

        }

       var sub_total = amt*quantiry;

       total = total + sub_total;

       sub_total = (sub_total).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');


       
       closesttr.find('.wooboi_sub_total span').html(sub_total);

       console.log(closesttr.find('.wooboi_sub_total span'));

   });

   total = (total).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

    jQuery('.wooboi_table_container').find('.wooboi_add_more_total_amt span').html(total);

}