<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://digitalzest.co.uk
 * @since      1.0.0
 *
 * @package    Aura_Publicity
 * @subpackage Aura_Publicity/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Aura_Publicity
 * @subpackage Aura_Publicity/includes
 * @author     Digital Zest <info@digitalzest.co.uk>
 */
class Aura_Publicity {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Aura_Publicity_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'AURA_PUBLICITY_VERSION' ) ) {
			$this->version = AURA_PUBLICITY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'aura-publicity';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_custom_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Aura_Publicity_Loader. Orchestrates the hooks of the plugin.
	 * - Aura_Publicity_i18n. Defines internationalization functionality.
	 * - Aura_Publicity_Admin. Defines all hooks for the admin area.
	 * - Aura_Publicity_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aura-publicity-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aura-publicity-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-aura-publicity-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-aura-publicity-public.php';

		/**
		 * The class responsible for defining all actions that deal with Snippet related functions.
		 */

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'snippets/snippets-controller.php';

		$this->loader = new Aura_Publicity_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Aura_Publicity_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Aura_Publicity_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Aura_Publicity_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'plugin_setup_menu_backend', 999);

		$this->loader->add_action( 'admin_init', $plugin_admin, 'check_required_plugins');
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'plugin_activate_admin_notice__fail');

		$this->loader->add_action( 'upgrader_process_complete', $plugin_admin, 'aura_upgrade_completed', 10, 2);
	}


	/**
	 * Register all of the hooks related to the custom functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @uses     AURA_PUBLICITY_SLUG to accomdoate child plugins calling class methods from their own files.
	 */
	private function define_custom_hooks() {

		$plugin_custom = new Aura_Publicity_Custom( $this->get_plugin_name(), $this->get_version(), AURA_PUBLICITY_SLUG );
		

		$this->loader->add_action( 'init', $plugin_custom, 'run_snippets', 999 );

		$this->loader->add_action( 'init', $plugin_custom, 'check_existing_snippets_callback' );

		$this->loader->add_action('admin_post_apm_snippet_form_submit', $plugin_custom, 'apm_snippet_form_submission');

		$this->loader->add_action( 'admin_post_apm_rebuild_snippets', $plugin_custom, 'apm_rebuild_snippets');
		$this->loader->add_action( 'admin_post_apm_refresh_snippets', $plugin_custom, 'apm_refresh_snippets');


	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Aura_Publicity_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Aura_Publicity_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
