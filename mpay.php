<?php

/**
 * Plugin Name:       MOMO MPAY
 * Description:       MOMO Pay Bulk Payments.
 * Version:           1.0.0
 * Author:            MPAY
 * Text Domain:       mpay
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('MPay')) :

	final class MPay
	{



		/**
		 * @var MPay $_instance to the the main and only instance of the MPay class.
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main reference to the plugins current version
		 */


		public $version     = '1.0.0';
		public $options_key = 'MPay';


		/**
		 * Global MPay Instance
		 *
		 * Ensure that only one instance of the main MPay class can be loaded.
		 *
		 * @return MPay Instance.
		 * @since 1.0.0
		 * @static
		 */

		/**
		 * A dependency injection container
		 *
		 * @var Object
		 */


		public $container = null;

		/**
		 * A MonoLog log object
		 *
		 * @var Object
		 */

		public $logger = null;

		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function __construct()
		{
			// Define constants that will be used throughout the plugin
			$this->define_constants();

			// Include the files and consequently the objects we need
			$this->includes();
		}

		/**
		 * Define MPay constants
		 *
		 * @since 1.0.0
		 */
		function define_constants()
		{
			define('MPAY_DIR', __DIR__);
			define('MPAY_FILE', __FILE__);
			define('MPAY_URL', plugin_dir_url(__FILE__));
		}

		/**
		 * Include the files we need
		 *
		 * @since 1.0.0
		 */
		public function includes()
		{
			require_once MPAY_DIR . '/vendor/autoload.php';
			require_once MPAY_DIR . '/includes/class-manage-accounts.php';
			require_once MPAY_DIR . '/includes/class-hook-registry.php';
		}
	}


	/**
	 * Returns the global MPay Instance.
	 *
	 * @since 1.8.0
	 */
	function MPay()
	{
		return MPay::instance();
	}

	MPay();
endif;
