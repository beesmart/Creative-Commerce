jQuery(document).ready(function(){
    
    if( jQuery("select.wooboi-chzn-select").length ){
        
        jQuery(".wooboi-chzn-select").chosen();
        
    }
        
    jQuery('.wooboi_add_more_link').click(function(){

        var data = jQuery('textarea[name="wooboi_add_more_textarea"]').val();

        jQuery('tr.wooboi_add_more_row').before(data);

        jQuery(".wooboi-chzn-select").chosen();

    });

    jQuery(document).on('click', 'a.wooboi_remove_rows', function(e) {

        jQuery(this).closest('tr').remove();
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
        jQuery('table.wooboi_table_container').find('select[name="wooboi_product_options[]"]').each(function(){

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

   jQuery('table.wooboi_table_container').find('select[name="wooboi_product_options[]"]').each(function(){

        var closesttr = jQuery(this).closest('tr');

        var provalues = closesttr.find('select').val();
        var quantiry = closesttr.find('input[type="number"]').val();

       if(quantiry == '' || quantiry == '0' ){
        closesttr.find('input[type="number"]').val(1)
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


   });

   total = (total).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

    jQuery('table.wooboi_table_container').find('.wooboi_add_more_total_amt span').html(total);

}