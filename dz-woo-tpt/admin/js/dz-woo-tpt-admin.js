jQuery(document).ready(function($) {

    jQuery('#tpt_export_csv_btn').click(function() {

      $.ajax({
        url: ajaxurl, // WordPress AJAX URL
        data: {
          'action': 'export_csv'
        },
        success: function(response) {
          // Create a temporary anchor and click to download
          let a = document.createElement('a');
          a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response);
          a.target = '_blank';
          a.download = 'export.csv';
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
        }
      });
    });

	jQuery('#tpt_rebuild_user_data').click(function() {
		console.log('clicked')
		$.ajax({
		  url: ajaxurl, // WordPress AJAX URL
		  data: {
			'action': 'rebuild_user_data'
		  },
		  success: function(response) {
			if (response.success) {
				alert(response.data);  // Outputs: "Operation completed successfully."
			} else {
				alert(response.data);  // Outputs: "An error occurred: ..."
			}
		  }
		});
	  });
 

 });
