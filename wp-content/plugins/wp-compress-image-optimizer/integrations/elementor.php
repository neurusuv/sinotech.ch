<?php

class wps_ic_elementor extends wps_ic_integrations {

	public function is_active() {
		return defined('ELEMENTOR_VERSION');
	}

	public function do_checks() {

	}

	public function fix_setting($setting) {

	}

	public function add_admin_hooks() {
		return [
			'elementor/core/files/clear_cache' => [
				'callback' => 'clear_cache',
				'priority' => 10,
				'args'     => 1
			],
			'elementor/maintenance_mode/mode_changed' => [
				'callback' => 'clear_cache',
				'priority' => 10,
				'args'     => 1
			],
			'update_option__elementor_global_css' => [
				'callback' => 'clear_cache',
				'priority' => 10,
				'args'     => 1
			],
			'delete_option__elementor_global_css' => [
				'callback' => 'clear_cache',
				'priority' => 10,
				'args'     => 1
			]
		];
	}

	public function clear_cache(){
		$cache = new wps_ic_cache_integrations();
		$cache::purgeAll();;
	}
}