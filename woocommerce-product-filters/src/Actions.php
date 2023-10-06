<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Handles queued up actions via the `WC_Action_Queue` class.
 */
class Actions implements Registerable {

	/**
	 * Register hooks that are handled by the queue.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wcf_batch_index', [ $this, 'process_batch_index' ], 10, 2 );
	}

	/**
	 * Listens to the queued action and dispatches indexing of a batch.
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @return void
	 */
	public function process_batch_index( $offset = 0, $limit = 0 ) {
		/** @var Indexer $indexer */
		$indexer = wcf()->get_service( 'indexer' );

		$indexer->index_batch( $offset, $limit );
	}

}
