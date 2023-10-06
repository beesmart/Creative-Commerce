<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Model\Filter;

/**
 * Helper class that determines if a reindexing is needed.
 *
 * The current and previous state of the filters is stored into
 * a database option that contains a series of information.
 *
 * The 2 options are then compared against eachother and when
 * certain criterias are met, a re-index is required.
 *
 * When a re-index is required, a notice in the WCF settings panel is displayed.
 */
class Diff {

	const INDEX_CURRENT_STATE = 'wcf_filters_state';

	/**
	 * Determine if the reindex is needed.
	 *
	 * @return boolean
	 */
	public static function is_reindex_needed() {

		$current_state   = get_option( self::INDEX_CURRENT_STATE, [] );
		$current_filters = Filter::get()->map->only( [ 'id', 'filter_by' ] )->toArray();

		if ( $current_state !== $current_filters ) {
			return true;
		}

		return false;
	}

	/**
	 * Update the state of the filters in the database.
	 *
	 * @return void
	 */
	public static function update_current_state() {

		$filters = Filter::get()->map->only( [ 'id', 'filter_by' ] )->toArray();

		update_option( self::INDEX_CURRENT_STATE, $filters );
	}


}
