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
 * Defines the groups database table schema.
 */
class Groups extends BaseSchema {

	public $table_name = Plugin::META_PREFIX . 'groups';

	/** {@inheritdoc } */
	public function create() {
		$this->db::schema()->create(
			$this->table_name,
			function ( Blueprint $table ) {
				$table->increments( 'id' );
				$table->string( 'name' );
				$table->integer( 'priority' );
				$table->json( 'filters' );
				$table->index( 'id' );
			}
		);
	}

}
