<?php

class Meow_MFRH_Admin extends MeowCommon_Admin {

	private $core = null;

	public function __construct( $allow_setup, $core ) {
		$this->core = $core;
		parent::__construct( MFRH_PREFIX, MFRH_ENTRY, MFRH_DOMAIN, class_exists( 'MeowPro_MFRH_Core' ) );
		if ( is_admin() ) {
			if ( $allow_setup ) {
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			}

			// Load the scripts only if they are needed by the current screen
			$uri = $_SERVER['REQUEST_URI'];
			$page = isset( $_GET["page"] ) ? $_GET["page"] : null;
			$is_media_library = preg_match( '/wp\-admin\/upload\.php/', $uri );
			$is_post_edit = preg_match( '/wp\-admin\/post\.php/', $uri );
			$is_mfrh_screen = in_array( $page, [ 'mfrh_dashboard', 'mfrh_settings' ] );
			$is_meowapps_dashboard = $page === 'meowapps-main-menu';
			if ( $is_meowapps_dashboard || $is_media_library || $is_mfrh_screen || $is_post_edit ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			}
		}
	}

	function admin_enqueue_scripts() {

		// Load the scripts
		$physical_file = MFRH_PATH . '/app/index.js';
		$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : MFRH_VERSION;
		wp_register_script( 'mfrh-vendor', MFRH_URL . 'app/vendor.js',
			['wp-element', 'wp-i18n'], $cache_buster
		);
		wp_register_script( 'mfrh', MFRH_URL . 'app/index.js',
			['mfrh-vendor', 'wp-i18n'], $cache_buster
		);
		wp_enqueue_script( 'mfrh' );

		// The MD5 of the translation file built by WP uses app/i18n.js instead of app/index.js
		add_filter( 'load_script_translation_file', function( $file, $handle, $domain ) {
			if ( $domain !== 'media-file-renamer' ) { return $file; }
			$file = str_replace( md5( 'app/index.js' ), md5( 'app/i18n.js' ), $file );
			return $file;
		}, 10, 3 );

		// Localize and options
		wp_set_script_translations( 'mwai', 'ai-engine' );
		wp_localize_script( 'mfrh', 'mfrh', [
			//'api_nonce' => wp_create_nonce( 'mfrh' ),
			'api_url' => get_rest_url(null, '/media-file-renamer/v1/'),
			'rest_url' => get_rest_url(),
			'plugin_url' => MFRH_URL,
			'prefix' => MFRH_PREFIX,
			'domain' => MFRH_DOMAIN,
			'is_pro' => class_exists( 'MeowPro_MFRH_Core' ),
			'is_registered' => !!$this->is_registered(),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'options' => $this->core->get_all_options(),
		] );
	}

	function is_pro_user() {
		return class_exists( 'MeowPro_MFRH_Core' ) && !!$this->is_registered();
	}

	function admin_menu() {
		add_submenu_page( 'meowapps-main-menu', __( 'Renamer', MFRH_DOMAIN ), __( 'Renamer', MFRH_DOMAIN ), 
			'read', 'mfrh_settings', array( $this, 'admin_settings' )
		);
	}

	public function admin_settings() {
		echo '<div id="mfrh-admin-settings"></div>';
	}
}

?>
