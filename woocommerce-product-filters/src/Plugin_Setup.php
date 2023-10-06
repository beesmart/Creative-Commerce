<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Starter;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Group;
use Barn2\Plugin\WC_Filters\Schema\Filters;
use Barn2\Plugin\WC_Filters\Schema\Groups;
use Barn2\Plugin\WC_Filters\Schema\Index;
use Barn2\Plugin\WC_Filters\Utils\Settings;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Plugin_Activation_Listener;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Hook into the plugin activation process.
 */
class Plugin_Setup implements Plugin_Activation_Listener, Registerable {
	/**
	 * Plugin's entry file
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Plugin instance
	 *
	 * @var Licensed_Plugin
	 */
	private $plugin;

	/**
	 * Wizard starter.
	 *
	 * @var Starter
	 */
	private $starter;

	/**
	 * Get things started
	 *
	 * @param string $file
	 */
	public function __construct( $file, Licensed_Plugin $plugin ) {
		$this->file   = $file;
		$this->plugin = $plugin;
		$this->starter = new Starter( $this->plugin );
	}

	/**
	 * Register the service
	 *
	 * @return void
	 */
	public function register() {
		register_activation_hook( $this->file, [ $this, 'on_activate' ] );
		add_action( 'admin_init', [ $this, 'after_plugin_activation' ] );
	}

	/**
	 * On plugin activation determine if the setup wizard should run.
	 *
	 * @return void
	 */
	public function on_activate() {
		// Network wide.
		// phpcs:disable
		$network_wide = ! empty( $_GET['networkwide'] )
			? (bool) $_GET['networkwide']
			: false;
		// phpcs:enable

		$filters_table = new Filters();
		$groups_table  = new Groups();
		$index_table   = new Index();

		$filters_table->register();
		$groups_table->register();
		$index_table->register();

		$this->install_default_data();

		if ( $this->starter->should_start() ) {
			$this->starter->create_transient();
		}
	}

	/**
	 * Create the default filters group and filters
	 * only when the groups database table is empty.
	 *
	 * @return void
	 */
	private function install_default_data() {
		$db = wcf()->get_service( 'db' );

		if ( empty( $db::table( 'wcf_groups' )->count() ) || $db::table( 'wcf_groups' )->count() === 0 ) {
			// First create the group.
			$group = Group::create(
				[
					'name'     => __( 'Product Filters', 'woocommerce-product-filters' ),
					'priority' => 0,
					'filters'  => []
				]
			);

			// Then create the filters.
			if ( $group instanceof Group && ! empty( $group->getID() ) ) {
				$this->install_filter(
					[
						'name'      => __( 'Categories', 'woocommerce-product-filters' ),
						'slug'      => 'categories_checkboxes',
						'filter_by' => 'categories',
						'options'   => [
							'filter_type' => 'checkboxes',
						],
						'priority'  => 0
					],
					$group->getID()
				);

				$this->install_filter(
					[
						'name'      => __( 'Attributes', 'woocommerce-product-filters' ),
						'slug'      => 'attributes_checkboxes',
						'filter_by' => 'attributes',
						'options'   => [
							'filter_type'     => 'checkboxes',
							'attributes_mode' => 'all',
						],
						'priority'  => 1
					],
					$group->getID()
				);

				$this->install_filter(
					[
						'name'      => __( 'Price', 'woocommerce-product-filters' ),
						'slug'      => 'price',
						'filter_by' => 'price',
						'options'   => [],
						'priority'  => 2
					],
					$group->getID()
				);

				$this->install_filter(
					[
						'name'      => __( 'Sort by', 'woocommerce-product-filters' ),
						'slug'      => 'sortby',
						'filter_by' => 'sorter',
						'options'   => [],
						'priority'  => 3
					],
					$group->getID()
				);
			}

			// Add the group the top filters.
			Settings::update_option( 'group_display_shop_archive', $group->getID() );

			// Set the horizontal layout to support 4 columns.
			Settings::update_option( 'horizontal_per_row', 4 );

			$this->set_indexing_transient();

		}

	}

	/**
	 * Generate a filter and assign it to a group.
	 *
	 * @param array $args
	 * @param string $group_id
	 * @return void
	 */
	private function install_filter( array $args, $group_id ) {

		$defaults = [
			'name'      => 'Filter name',
			'slug'      => 'slug',
			'filter_by' => 'categories',
			'options'   => [],
			'priority'  => 0
		];

		$args = wp_parse_args( $args, $defaults );

		$filter = Filter::create( $args );

		if ( $filter instanceof Filter && ! empty( $filter->getID() ) ) {
			$filter->update_groups( [ $group_id ] );
		}

	}

	/**
	 * Detect the transient and redirect to wizard.
	 *
	 * @return void
	 */
	public function after_plugin_activation() {
		if ( $this->should_start_indexing() ) {
			$this->delete_indexing_transient();
			$this->do_silent_index();
		}

		if ( ! $this->starter->detected() ) {
			return;
		}

		$this->starter->delete_transient();
		$this->starter->redirect();
	}

	/**
	 * Set a temporary transient that indicates that default data should be installed.
	 *
	 * @return void
	 */
	public function set_indexing_transient() {
		set_transient( "wcf_indexing_activation_redirect", true, 30 );
	}

	/**
	 * Delete the transient used to indicate that data indexing is required.
	 *
	 * @return void
	 */
	public function delete_indexing_transient() {
		delete_transient( "wcf_indexing_activation_redirect" );
	}

	/**
	 * Determines if data indexing should start.
	 *
	 * @return boolean
	 */
	public function should_start_indexing() {
		return get_transient( "wcf_indexing_activation_redirect" );
	}

	/**
	 * Trigger silent indexing.
	 *
	 * @return void
	 */
	public function do_silent_index() {
		/** @var \WC_Action_Queue $queue */
		$queue = wcf()->get_service( 'queue' );

		/** @var Indexer $indexer */
		$indexer = wcf()->get_service( 'indexer' );

		$indexer->set_index_running( true, true );

		Diff::update_current_state();

		$queue->schedule_single(
			time(),
			'wcf_batch_index',
			[
				'offset' => 0,
				'limit'  => $indexer->get_chunk_size(),
			],
			'wcf_batch_index'
		);
	}

	/**
	 * Do nothing.
	 *
	 * @return void
	 */
	public function on_deactivate() {}
}
