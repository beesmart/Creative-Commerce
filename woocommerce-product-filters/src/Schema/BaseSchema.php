<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Schema;

use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\DatabaseCapsule;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Base class that handles the registration of database tables for the plugin.
 */
abstract class BaseSchema implements Registerable {

	/**
	 * Name of the table to which the schema belongs to.
	 * Without db prefix.
	 *
	 * @var string
	 */
	public $table_name;

	/**
	 * Holds the database capsule service.
	 *
	 * @var DatabaseCapsule
	 */
	protected $db;

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->db = wcf()->get_service( 'db' );
	}

	/**
	 * Determine if the table currently exists.
	 *
	 * @return boolean
	 */
	private function has_table() {
		return $this->db::schema()->hasTable( $this->table_name );
	}

	/**
	 * Create the database table if it doesn't exist.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! $this->has_table() ) {
			$this->create();
		}
	}

	/**
	 * Create the database table.
	 *
	 * @return void
	 */
	abstract public function create();

}
