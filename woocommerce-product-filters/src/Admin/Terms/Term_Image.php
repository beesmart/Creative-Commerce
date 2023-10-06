<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Terms;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Str;
use Barn2\Plugin\WC_Filters\Utils\Products;

/**
 * Adds an image picker to categories and attributes.
 */
class Term_Image extends React_Term {

	public $file_name = 'wcf-term-image';

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		add_action( 'admin_init', [ $this, 'register_into_attribute_forms' ] );
	}

	/**
	 * Enqueue the media library assets.
	 *
	 * @return void
	 */
	public function assets() {
		if ( ! $this->should_enqueue() ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Determine if the react assets should enqueue.
	 *
	 * @return boolean
	 */
	public function should_enqueue() {
		$screen     = get_current_screen();
		$attributes = Products::get_registered_attributes();
		$taxonomies = Products::get_registered_taxonomies( true ) ?? [];

		if (
			Str::startsWith( $screen->taxonomy, 'pa_' ) && array_key_exists( Str::replaceFirst( 'pa_', '', $screen->taxonomy ), $attributes ) ) {
			return true;
		}

		if ( Str::startsWith( $screen->id, 'edit-' ) && array_key_exists( $screen->taxonomy, $taxonomies ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Find WC attributes and display out custom field for the picker.
	 *
	 * @return void
	 */
	public function register_into_attribute_forms() {
		$attributes = wc_get_attribute_taxonomies() ?? [];

		foreach ( $attributes as $attr ) {
			add_action( 'pa_' . $attr->attribute_name . '_add_form_fields', [ $this, 'output' ] );
			add_action( 'pa_' . $attr->attribute_name . '_edit_form_fields', [ $this, 'output_edit' ], 10, 2 );
		}

		$taxonomies = Products::get_registered_taxonomies( true ) ?? [];

		foreach ( $taxonomies as $tax => $name ) {
			add_action( $tax . '_add_form_fields', [ $this, 'output' ] );
			add_action( $tax . '_edit_form_fields', [ $this, 'output_edit' ], 10, 2 );
		}

	}

	/**
	 * Renders the picker dom element.
	 *
	 * @return void
	 */
	public function output() {
		?>
		<div class="form-field term-thumbnail-wrap">
			<label><?php esc_html_e( 'Thumbnail', 'woocommerce-product-filters' ); ?></label>
			<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;"><img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" width="60px" height="60px" /></div>
			<div style="line-height: 60px;">
				<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" />
				<button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce-product-filters' ); ?></button>
				<button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce-product-filters' ); ?></button>
			</div>
			<script type="text/javascript">

				// Only show the "remove image" button when needed
				if ( ! jQuery( '#product_cat_thumbnail_id' ).val() ) {
					jQuery( '.remove_image_button' ).hide();
				}

				// Uploading files
				var file_frame;

				jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

					event.preventDefault();

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.downloadable_file = wp.media({
						title: '<?php esc_html_e( 'Choose an image', 'woocommerce-product-filters' ); ?>',
						button: {
							text: '<?php esc_html_e( 'Use image', 'woocommerce-product-filters' ); ?>'
						},
						multiple: false
					});

					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {
						var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
						var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

						jQuery( '#product_cat_thumbnail_id' ).val( attachment.id );
						jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
						jQuery( '.remove_image_button' ).show();
					});

					// Finally, open the modal.
					file_frame.open();
				});

				jQuery( document ).on( 'click', '.remove_image_button', function() {
					jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
					jQuery( '#product_cat_thumbnail_id' ).val( '' );
					jQuery( '.remove_image_button' ).hide();
					return false;
				});

				jQuery( document ).ajaxComplete( function( event, request, options ) {
					if ( request && 4 === request.readyState && 200 === request.status
						&& options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {

						var res = wpAjax.parseAjaxResponse( request.responseXML, 'ajax-response' );
						if ( ! res || res.errors ) {
							return;
						}
						// Clear Thumbnail fields on submit
						jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
						jQuery( '#product_cat_thumbnail_id' ).val( '' );
						jQuery( '.remove_image_button' ).hide();
						// Clear Display type field on submit
						jQuery( '#display_type' ).val( '' );
						return;
					}
				} );

			</script>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Renders the picker dom element.
	 *
	 * @param \WP_Term $term
	 * @param string $taxonomy
	 * @return void
	 */
	public function output_edit( $term, $taxonomy ) {
		$thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );

		if ( $thumbnail_id ) {
			$image = wp_get_attachment_thumb_url( $thumbnail_id );
		} else {
			$image = wc_placeholder_img_src();
		}

		?>
		<tr class="form-field term-thumbnail-wrap">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Thumbnail', 'woocommerce-product-filters' ); ?></label></th>
			<td>
				<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;"><img src="<?php echo esc_url( $image ); ?>" width="60px" height="60px" /></div>
				<div style="line-height: 60px;">
					<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
					<button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce-product-filters' ); ?></button>
					<button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce-product-filters' ); ?></button>
				</div>
				<script type="text/javascript">

					// Only show the "remove image" button when needed
					if ( '0' === jQuery( '#product_cat_thumbnail_id' ).val() ) {
						jQuery( '.remove_image_button' ).hide();
					}

					// Uploading files
					var file_frame;

					jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if ( file_frame ) {
							file_frame.open();
							return;
						}

						// Create the media frame.
						file_frame = wp.media.frames.downloadable_file = wp.media({
							title: '<?php esc_html_e( 'Choose an image', 'woocommerce-product-filters' ); ?>',
							button: {
								text: '<?php esc_html_e( 'Use image', 'woocommerce-product-filters' ); ?>'
							},
							multiple: false
						});

						// When an image is selected, run a callback.
						file_frame.on( 'select', function() {
							var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
							var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

							jQuery( '#product_cat_thumbnail_id' ).val( attachment.id );
							jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
							jQuery( '.remove_image_button' ).show();
						});

						// Finally, open the modal.
						file_frame.open();
					});

					jQuery( document ).on( 'click', '.remove_image_button', function() {
						jQuery( '#product_cat_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
						jQuery( '#product_cat_thumbnail_id' ).val( '' );
						jQuery( '.remove_image_button' ).hide();
						return false;
					});

				</script>
				<div class="clear"></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the filter image attachment ID when creating or updating a category.
	 *
	 * @param string $term_id
	 * @param string $tt_id
	 * @param string $taxonomy
	 * @return void
	 */
	public function created_term( $term_id, $tt_id, $taxonomy ) {
		if ( ! isset( $_POST['product_cat_thumbnail_id'] ) ) {
			return;
		}

		$attachment_id = sanitize_text_field( $_POST['product_cat_thumbnail_id'] );

		if ( empty( $attachment_id ) ) {
			delete_term_meta( $term_id, 'thumbnail_id' );
			return;
		}

		update_term_meta( $term_id, 'thumbnail_id', absint( $attachment_id ) );
	}

}
