<?php

class wps_ic_rocket extends wps_ic_integrations {

	public function is_active() {
		return function_exists( 'get_rocket_option' );
	}

	public function do_checks() {
		// Logic to check for conflicts
		$rocket_settings = get_option( 'wp_rocket_settings' );

		if ( $this->wps_settings['delay-js'] && ! empty( $rocket_settings['delay_js'] ) && $rocket_settings['delay_js'] ) {
			echo('wtf');
			$this->notices_class->show_notice( 'WPCompress - Delay JS conflict detected',
				'Click "Fix" to use WPCompress and disable WP Rocket\'s setting, or "Dismiss" to continue.',
				'warning', true, 'wpc_rocket_delay_js_dismiss_tag', [ 'plugin' => 'rocket', 'setting' => 'delay_js' ] );

			$this->add_override('delay-js');
		}

		if ( $this->wps_settings['lazy'] && ! empty( $rocket_settings['lazyload'] ) && $rocket_settings['lazyload'] ) {
			$this->notices_class->show_notice( 'WPCompress - Lazy Load conflict detected',
				'Click "Fix" to use WPCompress and disable WP Rocket\'s setting, or "Dismiss" to continue.',
				'warning', true, 'wpc_rocket_lazyload_dismiss_tag',[ 'plugin' => 'rocket', 'setting' => 'lazyload' ] );

			$this->add_override('lazy');
		}

	}

	public function fix_setting( $setting ) {
		$rocket_settings = get_option( 'wp_rocket_settings' );

		if ( $setting == 'delay_js' ) {
			$rocket_settings['delay_js'] = 0;
			$this->remove_override('delay-js');
		} else if ( $setting == 'lazyload' ) {
			$rocket_settings['lazyload'] = 0;
			$this->remove_override('lazy');
		}

		return update_option( 'wp_rocket_settings', $rocket_settings );
	}

}