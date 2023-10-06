<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Schema;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Schema\Blueprint;
use Barn2\Plugin\WC_Filters\Plugin;

/**
 * Declares the filters indexing database table.
 */
class Index extends BaseSchema {

	public $table_name = Plugin::META_PREFIX . 'index';

	/** {@inheritdoc } */
	public function create() {
		$this->db::schema()->create(
			$this->table_name,
			function ( Blueprint $table ) {
				$table->bigIncrements( 'id' );
				$table->unsignedBigInteger( 'post_id' );
				$table->unsignedBigInteger( 'filter_id' );
				$table->string( 'facet_name', 50 );
				$table->string( 'facet_value', 50 );
				$table->string( 'facet_display_value', 200 );
				$table->unsignedBigInteger( 'term_id' )->default( 0 );
				$table->unsignedBigInteger( 'parent_id' )->default( 0 );
				$table->unsignedInteger( 'depth' )->default( 0 );
				$table->unsignedBigInteger( 'variation_id' )->default( 0 );

				$table->index( 'facet_name', 'facet_name_idx' );
				$table->index( 'filter_id', 'facet_id_idx' );
				$table->index( [ 'facet_name', 'facet_value' ], 'facet_name_value_idx' );
			}
		);
	}

}
