

<?php







?>



<div class="wp-tab-panel aura-super wrap" id="tabs-1" <?php if (!$show_admin_screens) : echo 'style="display: none;"'; endif; ?>>

	<div class="wrap">

		<div class="panel-heading">

			<h2>Snippets Dashboard</h2>

			<h4>Version: <?php echo $this->version ?></h4>

		</div>

		<div class="tab-content">

			<div class="tab-inner">

				

			

			<?php 

				if ( $show_admin_screens ) :



					?>

					<div class="forms-wrap">

						<form class="aura-form basic operations" action="<?php echo admin_url('admin-post.php'); ?>" method='post'>

							

							<input type='hidden' name='action' value='refresh_snippets'>

							<i class="fa-refresh fa"></i><input type='submit' value='Refresh Snippets' onclick="return confirm('This will scan again for New snippets, any new snippets will be turned on however the rest of the snippets statuses will be left alone');">

						</form>

						<form id="snippet-ops" class="aura-form basic operations" action="<?php echo admin_url('admin-post.php'); ?>" method='post'>

							

							<input type='hidden' name='action' value='rebuild_snippets'>

							<i class="fa-puzzle-piece fa"></i><input type='submit' value='Rebuild Snippets' onclick="return confirm('This will scan again for New snippets and switch all existing snippets to OFF');">

						</form>

					</div>

					<?php



					$aura_sc_custom  = new Aura_Supercommerce_Custom( $this->plugin_name, $this->version, AURA_SUPERCOMMERCE_SLUG );

					echo $aura_sc_custom->output_snippet_switches();



				endif;

			?>

			</div>

		</div>

	</div>

</div>