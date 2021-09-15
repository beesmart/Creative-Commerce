<?php



?>

<div class="wp-tab-panel aura-super wrap" id="tabs-4" style="display: none;">
	<div class="wrap"> 

		<div class="panel-heading">
			<h2>Licence Dashboard</h2>
			<h4>Version: <?php echo $this->version ?></h4>
		</div>

		<div class="tab-content">
			<div class="tab-inner key-info">

				<div class="key-status"><span class="key-title">Licence Key Status:</span> <?php echo aura_licence_licence_display();?>	
			    </div>

		    	<div class="key-operations key-status">
		    		<form id="licence-ops" class="aura-form basic operations align-left" action="<?php echo admin_url('admin-post.php'); ?>" method='post'>
		    			<span class="key-op-title key-title">Operations:</span> 
		    			<input type='hidden' name='action' value='delete_transients'>
		    			<input type='submit' value='Delete Transients'>
		    		</form>
		        </div>

			</div>
		</div>
	</div>
</div>