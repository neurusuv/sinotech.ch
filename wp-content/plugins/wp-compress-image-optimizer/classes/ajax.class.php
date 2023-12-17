<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

/**
 * Class - Ajax
 */
class wps_ic_ajax extends wps_ic
{

  static $API_URL = WPS_IC_CRITICAL_API_URL;
  static $API_ASSETS_URL = WPS_IC_CRITICAL_API_ASSETS_URL;

  public static $local;
  public static $options;
  public static $settings;
  public static $accountStatus;

  public static $logo_compressed;
  public static $logo_uncompressed;
  public static $logo_excluded;
  public static $allowed_types;
  public static $count_thumbs;

  public static $cacheIntegrations;

  public static $version;

  public function __construct()
  {
    if (is_admin()) {
      self::$version = str_replace('.', '', parent::$version);
      self::$cacheIntegrations = new wps_ic_cache_integrations();
      self::$settings = get_option(WPS_IC_SETTINGS);
      self::$options = get_option(WPS_IC_SETTINGS);
      self::$count_thumbs = count(get_intermediate_image_sizes());
      self::$local = parent::$local;
      self::$logo_compressed = WPS_IC_URI . 'assets/images/legacy/logo-compressed.svg';
      self::$logo_uncompressed = WPS_IC_URI . 'assets/images/legacy/logo-not-compressed.svg';
      self::$logo_excluded = WPS_IC_URI . 'assets/images/legacy/logo-excluded.svg';

      if (!empty(parent::$response_key)) {
        // Pull Stats
        $this->add_ajax('wps_ic_pull_stats');

        // Critical CSS
        $this->add_ajax('wps_ic_critical_get_assets');
        $this->add_ajax('wps_ic_critical_run');
        $this->add_ajax('wps_ic_get_setting');
        $this->add_ajax('wps_ic_save_excludes_settings');

        // GeoLocation for Popups
        $this->add_ajax('wps_ic_remove_key');
        $this->add_ajax('wpc_ic_set_mode');
        $this->add_ajax('wpc_ic_ajax_set_preset');
        $this->add_ajax('wps_ic_cname_add');
        $this->add_ajax('wps_ic_cname_retry');
        $this->add_ajax('wps_ic_remove_cname');
        $this->add_ajax('wps_ic_exclude_list');
        $this->add_ajax('wps_ic_geolocation');
        $this->add_ajax('wps_ic_geolocation_force');

        // Bulk Actions
        $this->add_ajax('wps_ic_StopBulk');
        $this->add_ajax('wps_ic_getBulkStats');
        $this->add_ajax('wps_ic_bulkCompressHeartbeat');
        $this->add_ajax('wps_ic_bulkRestoreHeartbeat');
        $this->add_ajax('wps_ic_isBulkRunning');
        $this->add_ajax('wpc_ic_start_bulk_restore');
        $this->add_ajax('wpc_ic_start_bulk_compress');
        $this->add_ajax('wps_ic_media_library_bulk_heartbeat');
        $this->add_ajax('wps_ic_doBulkRestore');
        $this->add_ajax('wps_ic_RestoreFinished');

        $this->add_ajax('wps_ic_media_library_heartbeat');
        $this->add_ajax('wps_ic_compress_live');
        $this->add_ajax('wps_ic_restore_live');
        $this->add_ajax('wps_ic_exclude_live');
        $this->add_ajax('wps_ic_get_default_settings');

        $this->add_ajax('wps_ic_ajax_v2_checkbox');
        $this->add_ajax('wps_ic_ajax_checkbox');

        $this->add_ajax('wps_ic_purge_cdn');
        $this->add_ajax('wps_ic_purge_html');
        $this->add_ajax('wps_ic_purge_critical_css');
        $this->add_ajax('wps_ic_preload_page');
        $this->add_ajax('wps_ic_generate_critical_css');

        $this->add_ajax('wps_ic_dismiss_notice');
	      $this->add_ajax('wps_ic_fix_notice');
	      $this->add_ajax('wps_ic_save_mode');

        // Live Start

        // First Run Variable
        $this->add_ajax('wps_ic_count_uncompressed_images');

        // Change Setting
        $this->add_ajax('wps_ic_settings_change');

        // Exclude Image from Compress
        $this->add_ajax('wps_ic_simple_exclude_image');
      } else {
        // Connect
        $this->add_ajax('wps_ic_live_connect');
      }

      $this->add_ajax('wpc_send_critical_remote');
      $this->add_ajax_nopriv('wpc_send_critical_remote');
    } else {
      $this->add_ajax('wpc_ic_set_mode');
      $this->add_ajax('wpc_send_critical_remote');
      $this->add_ajax_nopriv('wpc_send_critical_remote');
      $this->add_ajax('wps_ic_purge_html');
      $this->add_ajax('wps_ic_purge_cdn');
      $this->add_ajax('wps_ic_purge_critical_css');
      $this->add_ajax('wps_ic_preload_page');
      $this->add_ajax('wps_ic_generate_critical_css');
    }
  }

  public function add_ajax($hook)
  {
    add_action('wp_ajax_' . $hook, [$this, $hook]);
  }

  public function add_ajax_nopriv($hook)
  {
    add_action('wp_ajax_nopriv_' . $hook, [$this, $hook]);
  }

  public function wpc_send_critical_remote()
  {

    $criticalCSS = new wps_criticalCss();

	  $realUrl = urldecode($_POST['realUrl']);
    $realUrl = sanitize_text_field($realUrl);
    $postID = sanitize_text_field($_POST['postID']);

	  /**
	   * Only keep allowed params in url
	   */
	  $keys = new wps_ic_url_key();

	  $allowed_params = $keys->get_allowed_params();
	  $parsed_url = parse_url($realUrl);
	  parse_str($parsed_url['query'], $query_params);

		// Keep only the allowed parameters
	  $filtered_params = array_intersect_key($query_params, array_flip($allowed_params));

		// Build the new query string
	  $new_query = http_build_query($filtered_params);

		// Reconstruct the URL
	  $realUrl = $parsed_url['host'] . (isset($parsed_url['path']) ? $parsed_url['path'] : '') . '?' . $new_query;

		file_put_contents(WPS_IC_DIR.'/test.txt', $realUrl);


	  /**
     * Does Critical Already Exist?
     */
    $criticalCSSExists = $criticalCSS->criticalExistsAjax($realUrl);
    if (!empty($criticalCSSExists)) {
      wp_send_json_success(array('exists', $realUrl));
    }

    /**
     * Is Critical Ajax Already Running?
     */
    $running = get_transient('wpc_critical_ajax_' . $postID);
    if (!empty($running) && $running == 'true') {
      wp_send_json_success(array('already-running', $realUrl));
    }

    // Set as Running
    set_transient('wpc_critical_ajax_' . $postID, 'true', 60);

    $criticalCSS->sendCriticalUrl($realUrl, $postID);

    wp_send_json_success('sent');

    // Old Code Below

    $realUrl = sanitize_text_field($_POST['realUrl']);
    $postID = sanitize_text_field($_POST['postID']);
    $linkFull = get_permalink($postID);
    $criticalCSS = new wps_criticalCss($linkFull);

    /**
     * Does Critical Already Exist?
     */
    $criticalCSSExists = $criticalCSS->criticalExistsAjax($realUrl);
    if (!empty($criticalCSSExists)) {
      wp_send_json_success(array('exists', $realUrl));
    }

    /**
     * Is Critical Ajax Already Running?
     */
    $criticalRunning = $criticalCSS->criticalRunning();
    if (!empty($criticalRunning)) {
      wp_send_json_success(array('already-running', $realUrl));
    }

    if ($postID === 'home') {

    } elseif (!$postID || $postID == 0) {

      $homePage = get_option('page_on_front');
      $blogPage = get_option('page_for_posts');

      if (!$homePage) {
        $post['post_name'] = 'Home';
        $post = (object)$post;
        $url = site_url();
      } else {
        $post = get_post($homePage);
        $url = get_permalink($homePage);
      }

      $pages[$post->post_name] = $url;

      if ($blogPage !== 0 && $blogPage !== '0' && $blogPage !== $homePage) {
        $post = get_post($blogPage);
        $url = get_permalink($blogPage);
      }

      $pages[$post->post_name] = $url;
    } else {
      $post = get_post($postID);
      $url = get_permalink($postID);
      $pages[$post->post_name] = $url;
    }

    if (!empty($realUrl)) {
      $pages = array(sanitize_title($realUrl) => $realUrl);
    }

    if (is_multisite()) {
      $current_blog_id = get_current_blog_id();
      switch_to_blog($current_blog_id);
      $apikey = get_option(WPS_IC_OPTIONS)['api_key'];
    } else {
      $apikey = get_option(WPS_IC_OPTIONS)['api_key'];
    }

    foreach ($pages as $p => $url) {
      $pages[$p] = str_replace('http:', 'https:', $url);
    }

    $args = ['v7' => 'true', 'pages' => json_encode($pages), 'apikey' => $apikey, 'background' => 'true'];
    $call = wp_remote_post(self::$API_URL, ['timeout' => 10, 'blocking' => true, 'body' => $args, 'sslverify' => false, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);

    wp_send_json_success($args);
  }


  /**
   * Change Settings Value
   */
  public function wps_ic_settings_change()
  {
    global $wps_ic;

    $what = sanitize_text_field($_POST['what']);
    $value = sanitize_text_field($_POST['value']);
    $checked = sanitize_text_field($_POST['checked']);
    $checkbox = sanitize_text_field($_POST['checkbox']);


    $options = new wps_ic_options();
    $settings = $options->get_settings();

    if ($what == 'thumbnails') {
      if (!isset($value) || empty($value)) {
        $settings['thumbnails'] = [];
      } else {
        $settings['thumbnails'] = [];
        $value = rtrim($value, ',');
        $value = explode(',', $value);
        foreach ($value as $i => $thumb_size) {
          $settings['thumbnails'][$thumb_size] = 1;
        }
      }
    } else {
      if ($what == 'autopilot') {
        if ($checked == 'checked') {
        } else {
          $settings['otto'] = 'automated';
        }
      }

      if ($checkbox == 'true') {
        if ($checked === 'false') {
          $settings[$what] = 0;
        } else {
          $settings[$what] = 1;
        }
      } else {
        $settings[$what] = $value;
      }
    }

    if ($what == 'live_autopilot') {
      if ($value == '1') {
        // Enabline Live, clear local queue
        delete_option('wps_ic_bg_stop');
        delete_option('wps_ic_bg_process_stop');
        delete_option('wps_ic_bg_stopping');
        delete_option('wps_ic_bg_process');
        delete_option('wps_ic_bg_process_done');
        delete_option('wps_ic_bg_process_running');
        delete_option('wps_ic_bg_process_stats');
        delete_option('wps_ic_bg_last_run_compress');
        delete_option('wps_ic_bg_last_run_restore');
      }
    } elseif ($what == 'css' || $what == 'js') {
      // Purge CSS/JS Cache
      $this->purge_cdn_assets();
    }

    self::$cacheIntegrations->purgeAll();

    update_option(WPS_IC_SETTINGS, $settings);

    wp_send_json_success();
  }

  public function purge_cdn_assets()
  {
    $options = get_option(WPS_IC_OPTIONS);

    $call = wp_remote_get(WPS_IC_KEYSURL . '?action=cdn_purge&domain=' . site_url() . '&apikey=' . $options['api_key'], ['timeout' => '30', 'sslverify' => false, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);

    if (wp_remote_retrieve_response_code($call) == 200) {
      $body = wp_remote_retrieve_body($call);
      $body = json_decode($body);
      if ($body->success == 'true') {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  public function wps_ic_ajax_checkbox()
  {
    $setting_name = sanitize_text_field($_POST['setting_name']);
    $setting_value = sanitize_text_field($_POST['value']);
    $setting_checked = sanitize_text_field($_POST['checked']);

    $settings = get_option(WPS_IC_SETTINGS);

    // If it was checked then set to false as it's unchecked then
    if ($setting_checked == 'false') {
      $settings[$setting_name] = '0';
    } else {
      $settings[$setting_name] = '1';
    }

    if ($settings['live-cdn'] == '0') {
      $settings['js'] = '0';
      $settings['css'] = '0';
    }

    update_option(WPS_IC_SETTINGS, $settings);

    self::purgeBreeze();
    self::purge_cache_files();

    // Clear cache.
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }

    // Lite Speed
    if (defined('LSCWP_V')) {
      do_action('litespeed_purge_all');
    }

    // HummingBird
    if (defined('WPHB_VERSION')) {
      do_action('wphb_clear_page_cache');
    }

    wp_send_json_success(['new_value' => $settings[$setting_name], 'setting_name' => $setting_name, 'value' => $setting_value]);
  }

  /**
   * @return void
   */
  public static function purgeBreeze()
  {
    if (defined('BREEZE_VERSION')) {
      global $wp_filesystem;
      require_once(ABSPATH . 'wp-admin/includes/file.php');

      WP_Filesystem();

      $cache_path = breeze_get_cache_base_path(is_network_admin(), true);
      $wp_filesystem->rmdir(untrailingslashit($cache_path), true);

      if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
      }
    }
  }

  /**
   * @return bool
   */
  public static function purge_cache_files()
  {
    $cache_dir = WPS_IC_CACHE;

    self::removeDirectory($cache_dir);

    return true;
  }

  /**
   * TODO: Remove?
   * @param $path
   * @return void
   */
  public static function removeDirectory($path)
  {
    $path = rtrim($path, '/');
    $files = glob($path . '/*');
    if (!empty($files)) {
      foreach ($files as $file) {
        is_dir($file) ? self::removeDirectory($file) : unlink($file);
      }
    }
  }

  public function wps_ic_dismiss_notice()
  {
    $notice_dismiss_info = get_option('wps_ic_notice_info');
    $tag = sanitize_text_field($_POST['id']);

    if (!empty ($tag)) {
      $notice_dismiss_info[$tag] = 0;
      update_option('wps_ic_notice_info', $notice_dismiss_info);
      wp_send_json_success();
    }
    wp_send_json_error();

  }


	public function wps_ic_fix_notice()
	{
		$plugin = sanitize_text_field($_POST['plugin']);
		$setting = sanitize_text_field($_POST['setting']);

		if (!empty($plugin) && !empty($setting) ) {
			$integrations = new wps_ic_integrations();
			$fix = $integrations->fix($plugin, $setting);

			if ($fix){
				wp_send_json_success();
			}
		}
		wp_send_json_error();

	}

  /**
   * @return void
   */
  public function wps_ic_get_setting()
  {
		$option_name = sanitize_text_field($_POST['name']);
	  $option_subset = sanitize_text_field($_POST['subset']);

		if (!in_array($option_name, ['wpc-excludes', 'wpc-inline', 'wpc-url-excludes'])){
			wp_send_json_error('Forbidden.');
		}

    $option = get_option($option_name);
    $value = $option[$option_subset];
    $default_excludes = $option[$option_subset . '_default_excludes_disabled'];
	  $exclude_themes = $option[$option_subset . '_exclude_themes'];
	  $exclude_plugins = $option[$option_subset . '_exclude_plugins'];
	  $exclude_wp = $option[$option_subset . '_exclude_wp'];

    if (empty($value)) {
      $value = '';
    } else {
      $value = implode("\n", $value);
    }

    wp_send_json_success(['value' => $value,
                          'default_excludes' => $default_excludes,
                          'exclude_themes' => $exclude_themes,
                          'exclude_plugins' => $exclude_plugins,
	                        'exclude_wp' => $exclude_wp]);
  }

  public function wps_ic_save_excludes_settings()
  {

		if (!current_user_can('manage_options') || !wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' )){
			wp_send_json_error('Forbidden.');
		}

    $setting_name = sanitize_text_field($_POST['setting_name']);
    $setting_group = sanitize_text_field($_POST['group_name']);

		if ($setting_group == 'wpc-url-excludes'){
			//To be used in excluding url from an optimization option
			$excludes = $_POST['excludes'];
			$excludes = rtrim($excludes, "\n");
			$excludes = explode("\n", $excludes);


			$wpc_excludes = get_option($setting_group);
			$wpc_excludes[$setting_name] = $excludes;

			$updated = update_option($setting_group, $wpc_excludes);
		}
    else if ($setting_group == 'wpc-excludes' || $setting_group == 'wpc-inline' ) {
			$excludes = $_POST['excludes'];
			$excludes = rtrim($excludes, "\n");
			$excludes = explode("\n", $excludes);

			$default_enabled = sanitize_text_field($_POST['default_enabled']);
			$exclude_themes = sanitize_text_field($_POST['exclude_themes']);
			$exclude_plugins = sanitize_text_field($_POST['exclude_plugins']);
			$exclude_wp = sanitize_text_field($_POST['exclude_wp']);


			$wpc_excludes = get_option($setting_group);
			$wpc_excludes[$setting_name] = $excludes;
			$wpc_excludes[$setting_name . '_default_excludes_disabled'] = $default_enabled;
			$wpc_excludes[$setting_name . '_exclude_themes'] = $exclude_themes;
			$wpc_excludes[$setting_name . '_exclude_plugins'] = $exclude_plugins;
			$wpc_excludes[$setting_name . '_exclude_wp'] = $exclude_wp;

			$updated = update_option($setting_group, $wpc_excludes);
		} else {
			wp_send_json_error('Forbidden.');
		}


    if ($updated) {
      $cache = new wps_ic_cache_integrations();
      $cache::purgeAll();

			if ($setting_name == 'combine_js' || $setting_name == 'css_combine' || $setting_name == 'delay_js'){
				$cache::purgeCombinedFiles();
			}

			if ($setting_name == 'critical_css'){
				$cache::purgeCriticalFiles();
			}


    }


    wp_send_json_success($wpc_excludes);

  }

  /**
   * @return void
   */
  public function wps_ic_critical_run()
  {
    $criticalCSS = new wps_criticalCss();
    $criticalCSS->sendCriticalUrl('', $_POST['pageID']);
    wp_send_json_success();
  }


  public function wps_ic_pull_stats() {
    $options = get_option(WPS_IC_OPTIONS);
    $getStats = wp_remote_get(WPS_IC_KEYSURL . '?apikey=' . $options['api_key'] . '&action=pullStats',['timeout' => 10, 'sslverify' => 'false', 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);
    wp_send_json_success();
  }


  /**
   * @return void
   */
  public function wps_ic_critical_get_assets()
  {
    $criticalCSS = new wps_criticalCss();
    $count = $criticalCSS->sendCriticalUrlGetAssets('', $_POST['pageID']);
    wp_send_json_success($count);
  }

  /**
   * @return void
   */
  public function wps_ic_ajax_v2_checkbox()
  {
    $options = get_option(WPS_IC_SETTINGS);

    $optionName = sanitize_text_field($_POST['optionName']);
    $optionValue = sanitize_text_field($_POST['optionValue']);

    $optionName = explode(',', $optionName);

    if (is_array($optionName) && count($optionName) > 1) {
      $newValue = $options[$optionName[0]][$optionName[1]] = $optionValue;
    } else {
      $optionName = $optionName[0];
      $newValue = $options[$optionName] = $optionValue;
    }

    update_option(WPS_IC_SETTINGS, $options);

    self::purgeBreeze();
    self::purge_cache_files();

    // Clear cache.
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }

    // Lite Speed
    if (defined('LSCWP_V')) {
      do_action('litespeed_purge_all');
    }

    // HummingBird
    if (defined('WPHB_VERSION')) {
      do_action('wphb_clear_page_cache');
    }

    wp_send_json_success(['newValue' => $newValue, 'optionName' => $optionName]);
  }

  /**
   * @return void
   * @since 5.20.01
   */
  public function wps_ic_generate_critical_css()
  {
    $options = get_option(WPS_IC_OPTIONS);

    if (empty($options['api_key'])) {
      wp_send_json_error('API Key empty!');
    }

    $criticalCSS = new wps_criticalCss($_SERVER['HTTP_REFERER']);
    $criticalCSS->generateCriticalAjax();
    /*
    $url = 'https://preloader-ashburn.wpcompress.com/';

    $call = wp_remote_post($url, [
        'body'       => ['single_url' => $_SERVER['HTTP_REFERER']],
        'timeout'    => 30,
        'sslverify'  => 'false',
        'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0'
    ]);

    sleep(3);
*/
    wp_send_json_success();
  }


  /**
   * @return void
   * @since 5.20.01
   */
  public function wps_ic_preload_page()
  {
    $options = get_option(WPS_IC_OPTIONS);

    if (empty($options['api_key'])) {
      wp_send_json_error('API Key empty!');
    }

    $url = WPS_IC_PRELOADER_API_URL;

    $call = wp_remote_post($url, ['body' => ['single_url' => $_SERVER['HTTP_REFERER'], 'apikey' => $options['api_key']], 'timeout' => 30, 'sslverify' => 'false', 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);

    sleep(3);

    wp_send_json_success();
  }


  /**
   * @return void
   * @since 5.20.01
   */
  public function wps_ic_purge_html()
  {
    $options = get_option(WPS_IC_OPTIONS);

    if (empty($options['api_key'])) {
      wp_send_json_error('API Key empty!');
    }

    delete_transient('wps_ic_css_cache');
    delete_option('wps_ic_modified_css_cache');
    delete_option('wps_ic_css_combined_cache');

    $cache = new wps_ic_cache_integrations();
    $cache::purgeAll(false, true);

    // Todo: maybe remove?
    $cache::purgeCombinedFiles();

    set_transient('wps_ic_purging_cdn', 'true', 30);

    // Clear cache.
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }

    // Lite Speed
    if (defined('LSCWP_V')) {
      do_action('litespeed_purge_all');
    }

    // HummingBird
    if (defined('WPHB_VERSION')) {
      do_action('wphb_clear_page_cache');
    }

    $this->cacheLogic = new wps_ic_cache();
    $this->cacheLogic::removeHtmlCacheFiles(0); // Purge & Preload
    $this->cacheLogic::preloadPage(0); // Purge & Preload

    sleep(3);
    delete_transient('wps_ic_purging_cdn');
    wp_send_json_success();
  }

  /**
   * @return void
   * @since 5.20.01
   */
  public function wps_ic_purge_critical_css()
  {
    $options = get_option(WPS_IC_OPTIONS);

    if (empty($options['api_key'])) {
      wp_send_json_error('API Key empty!');
    }

    delete_transient('wps_ic_css_cache');
    delete_option('wps_ic_modified_css_cache');
    delete_option('wps_ic_css_combined_cache');

    $cache = new wps_ic_cache_integrations();
    $cache::purgeCriticalFiles();
	  $cache::purgeCacheFiles();
		
    set_transient('wps_ic_purging_cdn', 'true', 30);

    // Clear cache.
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }

    // Lite Speed
    if (defined('LSCWP_V')) {
      do_action('litespeed_purge_all');
    }

    // HummingBird
    if (defined('WPHB_VERSION')) {
      do_action('wphb_clear_page_cache');
    }

    sleep(3);
    delete_transient('wps_ic_purging_cdn');
    wp_send_json_success();
  }

  /**
   * @return void
   * @since 5.20.01
   */
  public function wps_ic_purge_cdn()
  {
    $options = get_option(WPS_IC_OPTIONS);

    if (empty($options['api_key'])) {
      wp_send_json_error('API Key empty!');
    }

    $cacheHtml = new wps_cacheHtml();
    $cacheHtml->removeCacheFiles(0);

    $hash = time();
    $options['css_hash'] = $hash;
    $options['js_hash'] = $hash;
    update_option(WPS_IC_OPTIONS, $options);

    delete_transient('wps_ic_css_cache');
    delete_option('wpc_preloaded_status');
    delete_option('wps_ic_modified_css_cache');
    delete_option('wps_ic_css_combined_cache');

    set_transient('wps_ic_purging_cdn', 'true', 30);
    $url = WPS_IC_KEYSURL . '?action=cdn_purge&apikey=' . $options['api_key'];

    $call = wp_remote_get($url, ['timeout' => 10, 'sslverify' => 'false', 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);

    // Clear cache.
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }

    // Lite Speed
    if (defined('LSCWP_V')) {
      do_action('litespeed_purge_all');
    }

    // HummingBird
    if (defined('WPHB_VERSION')) {
      do_action('wphb_clear_page_cache');
    }

    sleep(3);
    delete_transient('wps_ic_purging_cdn');
    wp_send_json_success();

    // Ignore this below, we just do a trigger
    if (wp_remote_retrieve_response_code($call) == 200) {
      $body = wp_remote_retrieve_body($call);
      $body = json_decode($body);
      if ($body->success == 'true') {
        delete_transient('wps_ic_purging_cdn');
        //Clear preloaded pages list
        update_option('wpc_preloaded_status', []);
        wp_send_json_success();
      }
    }

    wp_send_json_error('Could not call purge action!');
  }


  /**
   * Exclude the image
   * @since 4.0.0
   */
  public function wps_ic_exclude_live()
  {
    global $wps_ic;

    $output = '';
    $action = sanitize_text_field($_POST['do_action']);
    $attachment_id = sanitize_text_field($_POST['attachment_id']);
    $filedata = get_attached_file($attachment_id);
    $basename = sanitize_title(basename($filedata));
    $exclude_list = get_option('wps_ic_exclude_list');

    if (!$exclude_list) {
      $exclude_list = array();
    }

    $exclude = get_post_meta($attachment_id, 'wps_ic_exclude_live', true);

    $filedata = get_attached_file($attachment_id);

    // Get scaled file size
    $filesize = filesize($filedata);
    $wpScaledFilesize = wps_ic_format_bytes($filesize, null, null, false);

    // Get original filesize
    $originalFilepath = wp_get_original_image_path($attachment_id);
    $originalFilesize = filesize($originalFilepath);
    $filesize = wps_ic_format_bytes($originalFilesize, null, null, false);

    if ($action == 'exclude') {
      $exclude_list[$attachment_id] = $basename;
      update_post_meta($attachment_id, 'wps_ic_exclude_live', 'true');

      $output .= '<div class="wps-ic-compressed-logo">';
      $output .= '<img src="' . self::$logo_excluded . '" />';
      $output .= '</div>';

      $output .= '<div class="wps-ic-compressed-info">';

      $output .= '<div class="wpc-info-box">';
      $output .= '<h5>Excluded</h5>';
      $output .= '</div>';

      $output .= '<div>';
      $output .= '<ul class="wpc-inline-list">';

      $output .= '<li><div class="wpc-savings-tag">' . $filesize . '</div></li>';

      $output .= '<li>';
      $output .= '<a class="wpc-dropdown-btn wps-ic-include-live ic-tooltip" title="Include" data-action="include" data-attachment_id="' . $attachment_id . '"></a>';
      $output .= '</li>';

      $output .= '</ul>';
      $output .= '</div>';

      $output .= '</div>';
    } else {
      unset($exclude_list[$attachment_id]);
      delete_post_meta($attachment_id, 'wps_ic_exclude_live');

      $output .= '<div class="wps-ic-compressed-logo">';
      $output .= '<img src="' . self::$logo_uncompressed . '" />';
      $output .= '</div>';

      $output .= '<div class="wps-ic-compressed-info">';

      $output .= '<div class="wpc-info-box">';
      $output .= '<h5>Not Compressed</h5>';
      $output .= '</div>';

      $output .= '<div>';
      $output .= '<ul class="wpc-inline-list">';

      $output .= '<li><div class="wpc-savings-tag">' . $filesize . '</div></li>';

      $output .= '<li>';
      $output .= '<a class="wpc-dropdown-btn wps-ic-compress-live ic-tooltip" title="Compress" data-attachment_id="' . $attachment_id . '"></a>';
      $output .= '</li>';
      $output .= '<li>';
      $output .= '<a class="wpc-dropdown-btn wps-ic-exclude-live ic-tooltip" title="Exclude" data-action="exclude" data-attachment_id="' . $attachment_id . '"></a>';
      $output .= '</li>';

      $output .= '</ul>';
      $output .= '</div>';

      $output .= '</div>';
    }

    update_option('wps_ic_exclude_list', $exclude_list);
    wp_send_json_success(['html' => $output]);
  }


  /**
   * Exclude the image
   * @since 4.0.0
   */
  public function wps_ic_simple_exclude_image()
  {
    global $wps_ic;
    $wps_ic = new wps_ic_compress();
    $wps_ic->simple_exclude($_POST, 'html');
  }


  /**
   * Connect Multsites With API
   */
  public function wps_ic_api_mu_connect()
  {
    global $wps_ic;

    // Is localhost?
    $sites = get_sites();

    // API Key
    $apikey = sanitize_text_field($_POST['apikey']);
    $affiliate_code = get_option('wps_ic_affiliate_code');

    if ($sites && is_multisite()) {
      $error = false;

      foreach ($sites as $key => $site) {
        // Setup URI
        $uri = WPS_IC_KEYSURL;
        $uri .= '?action=connect';
        $uri .= '&apikey=' . $apikey;
        $uri .= '&site=' . urlencode($site->domain . $site->path);
        $uri .= '&affiliate_code=' . $affiliate_code;

        // Verify API Key is our database and user has is confirmed getresponse
        $get = wp_remote_get($uri, ['timeout' => 120, 'sslverify' => false]);

        if (wp_remote_retrieve_response_code($get) == 200) {
          $body = wp_remote_retrieve_body($get);
          $body = json_decode($body);

          if ($body->success && $body->data->api_key != '' && $body->data->response_key != '') {
            $options = new wps_ic_options();
            $options->set_option('api_key', $body->data->api_key);
            $options->set_option('response_key', $body->data->response_key);
            $options->set_option('orp', $body->data->orp);

            $settings = get_option(WPS_IC_SETTINGS);

            $sizes = get_intermediate_image_sizes();
            foreach ($sizes as $key => $value) {
              $settings['thumbnails'][$value] = 1;
            }

            update_option(WPS_IC_SETTINGS, $settings);
            //delete_option('wps_ic_affiliate_code');
          }
        } else {
          $error = true;
        }
      }

      if ($error) {
        wp_send_json_error($body->data);
      } else {
        wp_send_json_success();
      }
    }

    wp_send_json_error('0');
  }


  /**
   * Connect With API
   */
  public function wps_ic_live_connect()
  {
    // API Key
    $apikey = sanitize_text_field($_POST['apikey']);
    $siteurl = urlencode(site_url());
    delete_option('wpsShowAdvanced');

    // Setup URI
    //    if (defined('WPS_IC_ENV') && WPS_IC_ENV == 'dev') {
    //      $uri = WPS_IC_KEYSURL . '?action=connectV6&apikey=' . $apikey . '&domain=' . $siteurl . '&plugin_version=' . self::$version . '&hash=' . md5(time()) . '&time_hash=' . time();
    //    } else {
    //      $uri = WPS_IC_KEYSURL . '?action=connect_v6&apikey=' . $apikey . '&domain=' . $siteurl . '&plugin_version=' . self::$version . '&hash=' . md5(time()) . '&time_hash=' . time();
    //    }

    $uri = WPS_IC_KEYSURL . '?action=connectV6&apikey=' . $apikey . '&domain=' . $siteurl . '&plugin_version=' . self::$version . '&hash=' . md5(time()) . '&time_hash=' . time();

    // Verify API Key is our database and user has is confirmed getresponse
    $get = wp_remote_get($uri, ['timeout' => 45, 'sslverify' => false, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);

    if (wp_remote_retrieve_response_code($get) == 200) {
      $body = wp_remote_retrieve_body($get);
      $body = json_decode($body);

      if (!empty($body->data->code) && $body->data->code == 'site-user-different') {
        // Popup Site Already Connected
        wp_send_json_error(array('msg' => 'site-already-connected', 'url' => $uri));
      }


      if ($body->success && $body->data->apikey != '' && $body->data->response_key != '') {
        $options = new wps_ic_options();
        $options->set_option('api_key', $body->data->apikey);
        $options->set_option('response_key', $body->data->response_key);

        // CDN Does exist or we just created it
        $zone_name = $body->data->zone_name;

        if (!empty($zone_name)) {
          update_option('ic_cdn_zone_name', $zone_name);
        }

        $settings = get_option(WPS_IC_SETTINGS);
        $sizes = get_intermediate_image_sizes();
        if($sizes) {
          foreach ($sizes as $key => $value) {
            $settings['thumbnails'][$value] = 1;
          }
        }

        $default_Settings = ['js' => '1', 'css' => '0', 'css_image_urls' => '0', 'external-url' => '0', 'replace-all-link' => '0', 'emoji-remove' => '0', 'disable-oembeds' => '0', 'disable-gutenber' => '0', 'disable-dashicons' => '0', 'on-upload' => '0', 'defer-js' => '0', 'serve' => ['jpg' => '1', 'png' => '1', 'gif' => '1', 'svg' => '1'], 'search-through' => 'html', 'preserve-exif' => '0', 'minify-css' => '0', 'minify-js' => '0'];

        $settings = array_merge($settings, $default_Settings);

        $settings['live-cdn'] = '1';
        update_option(WPS_IC_SETTINGS, $settings);

        // TODO: Setup the Cache Options, if cache is active

        wp_send_json_success(array('liveMode' => $body->data->liveMode, 'localMode' => $body->data->localMode));
      }

      wp_send_json_error(['uri' => $uri, 'body' => wp_remote_retrieve_body($get), 'code' => wp_remote_retrieve_response_code($get), 'get' => $get]);
    } else {
      wp_send_json_error(['Cannot Call API', $uri, wp_remote_retrieve_body($get), wp_remote_retrieve_response_code($get), wp_remote_retrieve_response_message($get)]);
    }

    wp_send_json_error('0');
  }


  /**
   * Deauthorize site with remote api
   */
  public function wps_ic_deauthorize_api()
  {
    global $wps_ic;

    // Vars
    $site = site_url();
    $options = new wps_ic_options();
    $apikey = $options->get_option('api_key');

    // Setup URI
    $uri = WPS_IC_KEYSURL . '?action=disconnect&apikey=' . $apikey . '&site=' . urlencode($site);

    // Verify API Key is our database and user has is confirmed getresponse
    $get = wp_remote_get($uri, ['timeout' => 30, 'sslverify' => false, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);

    $options->set_option('api_key', '');
    $options->set_option('response_key', '');
    $options->set_option('orp', '');
  }


  /**
   * Heartbeat
   */
  public function wps_ic_media_library_heartbeat()
  {
    global $wps_ic, $wpdb;
    $html = array();

    $heartbeatData = $wpdb->get_results("SELECT * FROM " . $wpdb->options . " WHERE option_name LIKE '_transient_wps_ic_heartbeat_%'");
    if (!$heartbeatData) wp_send_json_error();

    foreach ($heartbeatData as $transient) {
      $data = maybe_unserialize($transient->option_value);

      $imageID = $data['imageID'];
      $status = $data['status'];

      if ($status == 'compressed') {
        $html[$imageID] = $wps_ic->media_library->compress_details($imageID);
      } elseif ($status == 'restored') {
        $html[$imageID] = $wps_ic->media_library->compress_details($imageID);
      }

      delete_transient('wps_ic_compress_' . $imageID);
      delete_transient('wps_ic_heartbeat_' . $imageID);
    }

    wp_send_json_success(array('html' => $html));
  }


  public function wps_ic_bulkRestoreHeartbeat()
  {
    $isDone = get_transient('wps_ic_bulk_done');
    $parsedImages = get_option('wps_ic_parsed_images');
    $bulkStatus = get_option('wps_ic_BulkStatus');

    $bulkProcess = get_option('wps_ic_bulk_process');
    if ($bulkProcess && $bulkProcess['status'] != 'restoring') {
      wp_send_json_error(array('msg' => 'bulk-process-failed'));
    }


    if ($isDone) {
      $output = array();
      //
      $bulkStatus = get_option('wps_ic_BulkStatus');
      // Total Images in Restore Queue
      $imagesInRestoreQueue = $bulkStatus['foundImageCount'];
      $imagesRestored = $bulkStatus['restoredImageCount'];
      $progressBar = round(($imagesRestored / $imagesInRestoreQueue) * 100);
      //
      $output['status'] = 'done';
      $output['finished'] = $imagesRestored;
      $output['total'] = $imagesInRestoreQueue;
      $output['progress'] = $progressBar;

      delete_option('wps_ic_bulk_process');
      wp_send_json_success($output);
    }

    // Not ready for output, nothing is done yet
    if (empty($parsedImages)) {
      wp_send_json_success(array('status' => 'parsing'));
    }

    // Total Images in Restore Queue
    $imagesInRestoreQueue = $bulkStatus['foundImageCount'];
    $imagesRestored = $bulkStatus['restoredImageCount'];

    $progressBar = round(($imagesRestored / $imagesInRestoreQueue) * 100);

    // Bugfix, remove total index
    $onlyImages = $parsedImages;
    unset($onlyImages['total']);

    if (!empty($onlyImages)) {
      $lastID = array_key_last($onlyImages);
    }

    $lastProgress = $_POST['lastProgress'];

	  $stuck_check = get_transient('wps_ic_stuck_check');
	  if ($stuck_check['last_image'] == $lastID){
		  $stuck_check['count']++;
		  if ($stuck_check['count'] > 10){
			  self::$local->restartRestoreWorker();
			  $stuck_check['count'] = 0;
		  }
	  } else {
		  $stuck_check['last_image'] = $lastID;
		  $stuck_check['count'] = 0;
	  }
	  set_transient('wps_ic_stuck_check', $stuck_check, 120);

    $output = array();
    $output['status'] = 'working';
    $output['parsedImages'] = $parsedImages;
    $output['html'] = $this->bulkRestoreHtml($lastID, $lastProgress);
    $output['finished'] = $imagesRestored;
    $output['total'] = $imagesInRestoreQueue;
    $output['progress'] = $progressBar;
    $output['parsedImage'] = $parsedImages[$lastID];

    if ($imagesRestored >= $imagesInRestoreQueue) {
      delete_option('wps_ic_bulk_process');
      set_transient('wps_ic_bulk_done', true, 60);
    }

    wp_send_json_success($output);
  }


  public function wps_ic_bulkCompressHeartbeat()
  {
//    ini_set('display_errors', 1);
//    error_reporting(E_ALL);

    $isDone = get_transient('wps_ic_bulk_done');
    $parsedImages = get_option('wps_ic_parsed_images');
    $bulkStatus = get_option('wps_ic_BulkStatus');
    $bulkProcess = get_option('wps_ic_bulk_process');
		$counter = get_option( 'wps_ic_bulk_counter' );

    if ($bulkProcess && $bulkProcess['status'] != 'compressing') {
      wp_send_json_error(array('msg' => 'bulk-process-failed'));
    }

    if ($isDone) {
      $output = array();
      //
      $output['status'] = 'done';
      //
      delete_option('wps_ic_bulk_process');
	    delete_transient('wps_ic_stuck_check');
	    delete_option('wps_ic_bulk_counter');
      //
      wp_send_json_success($output);
    }

    // Not ready for output, nothing is done yet
    if (empty($parsedImages)) {
      wp_send_json_success(array('status' => 'parsing'));
    }

    // Bugfix, remove total index
    $onlyImages = $parsedImages;
    unset($onlyImages['total']);
    if (!empty($onlyImages)) {
      $lastID = array_key_last($onlyImages);
    }

    // Total Images Found
    $totalImagesFound = $bulkStatus['foundImageCount'];
    $totalThumbsFound = $bulkStatus['foundThumbCount'];

    // All Images Data
    $originalSize = $parsedImages['total']['original'];
    $compressedSize = $parsedImages['total']['compressed'];
    $imagesAndThumbs = $counter['imagesAndThumbs'];
    $imagesOnly = $counter['images'];

    // Last Image Data
    $lastImageOriginal = $parsedImages[$lastID]['total']['original'];
    $lastImageCompressed = $parsedImages[$lastID]['total']['compressed'];
    $savingsKb = $lastImageOriginal - $lastImageCompressed;

    // Avg Savings
    $avgReduction = (1 - (($compressedSize / $imagesAndThumbs) / ($originalSize / $imagesAndThumbs))) * 100;
    $avgReduction = number_format($avgReduction, 1);
    $avgReductionHTML = '<h3>' . $avgReduction . '%</h3><h5>Average Savings</h5>';

    // Total Savings
    $bulkSavings = wps_ic_format_bytes($originalSize - $compressedSize, null, null, false);
    $bulkSavingsHTML = '<h3>' . $bulkSavings . '</h3><h5>Total Savings</h5>';

    // Compressed Images
    $CompressedImagesHTML = '<h3>' . $imagesOnly . '/' . $totalImagesFound . '</h3><h5>Original Images</h5>';
    $CompressedThumbsHTML = '<h3>' . $imagesAndThumbs . '/' . $totalThumbsFound . '</h3><h5>Total Images</h5>';

    $stats = get_post_meta($lastID, 'ic_stats', true);
    $original_filesize = $stats['original']['original']['size'];
    $compressed_filesize = $stats['original']['compressed']['size'];

    $status = '';
    $status .= '<ul class="wps-icon-list">';
    $status .= '<li><i class="wps-icon saved"></i> ' . wps_ic_format_bytes($original_filesize - $compressed_filesize) . ' Saved</li>';
    $status .= '<li><i class="wps-icon quality"></i> ' . ucfirst(self::$settings['optimization']) . ' Mode</li>';
    if (self::$settings['generate_webp'] == '1') {
      $status .= '<li><i class="wps-icon webp"></i> WebP Generated</li>';
    }
    $status .= '</ul>';

    $full = wp_get_original_image_url($lastID);
    $imageFileName = basename($full);

    $progressBar = round(($imagesOnly / $totalImagesFound) * 100);

    $output = array();

	  $stuck_check = get_transient('wps_ic_stuck_check');
	  if ($stuck_check['last_image'] == $imageFileName){
		  $stuck_check['count']++;
			if ($stuck_check['count'] > 10){
				self::$local->restartCompressWorker();
				$stuck_check['count'] = 0;
			}
	  } else {
		  $stuck_check['last_image'] = $imageFileName;
		  $stuck_check['count'] = 0;
	  }
	  set_transient('wps_ic_stuck_check', $stuck_check, 120);

    $output['parsedImages'] = $parsedImages;
    $output['html'] = $this->bulkCompressHtml($lastID);
    $output['status'] = $status;
    $output['progress'] = $progressBar;
    $output['parsedImage'] = $parsedImages[$lastID];
    $output['lastFileName'] = $imageFileName;
    $output['progressAvgReduction'] = $avgReductionHTML;
    $output['progressTotalSavings'] = $bulkSavingsHTML;
    $output['progressCompressedImages'] = $CompressedImagesHTML;
    $output['progressCompressedThumbs'] = $CompressedThumbsHTML;

    if ($imagesOnly >= $totalImagesFound) {
      delete_option('wps_ic_bulk_process');
      set_transient('wps_ic_bulk_done', true, 60);
    }

    wp_send_json_success($output);
  }


  public function wps_ic_StopBulk()
  {
    global $wpdb;

    $local = new wps_ic_local();
    $send = $local->sendToAPI(array('stop'), '', 'stopBulk');
    if ($send) {
      delete_option('wps_ic_parsed_images');
      delete_option('wps_ic_BulkStatus');
      delete_option('wps_ic_bulk_process');
      set_transient('wps_ic_bulk_done', true, 60);

      // Delete all transients
      $query = $wpdb->query("DELETE FROM " . $wpdb->options . " WHERE option_name LIKE '%wps_ic_compress_%'");
      wp_send_json_success();
    }
  }


  public function wps_ic_getBulkStats()
  {
    $output = '';
    $output .= '<div class="wps-ic-bulk-html-wrapper">';
    $output .= '<div class="wps-ic-bulk-header">';
    $output .= '<div class="wps-ic-bulk-logo">';


    $output .= '<div class="logo-holder">';
    $output .= '<img src="' . WPS_IC_URI . 'assets/images/bulk/compress-complete.svg' . '">';
    $output .= '</div>';

    if ($_POST['type'] == 'compress') {
      $output .= '<div class="wps-ic-percent-savings">';
      $output .= '<h2>Image Compression Complete!</h2>';
      $output .= '</div>';
    } else {
      $output .= '<div class="wps-ic-percent-savings" style="margin-bottom:40px;">';
      $output .= '<h2>Image Restore Complete</h2>';
      $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    delete_option('wps_ic_parsed_images');
    delete_option('wps_ic_BulkStatus');
    delete_option('wps_ic_bulk_process');
    set_transient('wps_ic_bulk_done', true, 60);

    wp_send_json_success(['html' => $output]);
  }


  public function bulkCompressHtml($imageID)
  {
    $output = '';

    $thumbnail = wp_get_attachment_image_src($imageID, 'large');
    $full = wp_get_attachment_image_src($imageID, 'full');

    $backup_images = get_post_meta($imageID, 'ic_backup_images', true);
    $stats = get_post_meta($imageID, 'ic_stats', true);
    if (empty($stats)) {
      $uploadfile  = get_attached_file($imageID);
      $stats['original']['original']['size'] = filesize($uploadfile);
    }

    $image_filename = basename($thumbnail[0]);
    $image_full_filename = basename($full[0]);

    // Does the backup exist, if not replace with original
    if (!empty($backup_images['full']) && !file_exists($backup_images['full'])) {
      $original_image = $thumbnail[0];
    } else {
      $original_image = $full[0];
    }



    $original_filesize = wps_ic_format_bytes($stats['original']['original']['size'], null, null, false);
    $compressed_filesize = wps_ic_format_bytes($stats['original']['compressed']['size'], null, null, false);
    $savings_kb = wps_ic_format_bytes($stats['full']['original']['size'] - $stats['full']['compressed']['size'], null, null, false);

    if ($stats['original']['original']['size'] > 0 && $stats['original']['compressed']['size'] > 0) {
      $savings = 1 - ($stats['original']['compressed']['size'] / $stats['original']['original']['size']);
      $savings = round($savings * 100, 1);
    }

    $output .= '<div class="wps-ic-bulk-html-wrapper">';

    $output .= '<div class="wps-ic-bulk-header">';
    $output .= '<div class="wps-ic-bulk-before">';
    $output .= '<div class="image-holder">';

    $output .= '<div class="image-holder-inner">';
    $output .= '<div style="background-image:url(' . $original_image . ');" class="image-holder-bg"></div>';
    $output .= '</div>';

    $output .= '<div class="image-info-holder">';
    $output .= '<h4>Before</h4>';
    $output .= '<h3>' . $original_filesize . '</h3>';
    $output .= '</div>';

    $output .= '</div>';
    $output .= '</div>';
    $output .= '<div class="wps-ic-bulk-logo">';
    $output .= '<div class="logo-holder">';
    $output .= '<div class="wps-ic-bulk-preparing-logo-container">
        <div class="wps-ic-bulk-preparing-logo">
          <img src="' . WPS_IC_URI . 'assets/images/logo/blue-icon.svg" class="bulk-logo-prepare"/>
          <img src="' . WPS_IC_URI . 'assets/preparing.svg" class="bulk-preparing"/>
        </div>
      </div>';
    $output .= '</div>';
    $output .= '<div class="wps-ic-percent-savings">';
    $output .= '<h3>' . $savings . '% Savings</h3>';
    $output .= '</div>';
    $output .= '<div class="wps-ic-bulk-loading">';
    $output .= '';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '<div class="wps-ic-bulk-after">';
    $output .= '<div class="image-holder">';

    $output .= '<div class="image-holder-inner">';
    $output .= '<div style="background-image:url(' . $thumbnail[0] . ');" class="image-holder-bg"></div>';
    $output .= '</div>';

    $output .= '<div class="image-info-holder">';
    $output .= '<h4>After</h4>';
    $output .= '<h3>' . $compressed_filesize . '</h3>';
    $output .= '</div>';

    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '</div>';

    return $output;
  }


  /**
   * @return void
   * @since v6
   */
  public function wps_ic_isBulkRunning()
  {
    // Default
    $output = 'not-running';

    // Check the option
    $bulkRunning = get_option('wps_ic_bulk_process');
    if ($bulkRunning) {
      if (!empty($bulkRunning['status'])) {
        if ($bulkRunning['status'] == 'compressing') {
          $output = 'compressing';
        } else {
          $output = 'restoring';
        }

        wp_send_json_success($output);
      }
    }

    wp_send_json_error($output);
  }


  public function olderBackup($imageID)
  {
    return false;
    $backup_images = get_post_meta($imageID, 'ic_backup_images', true);

    if (!empty($backup_images) && is_array($backup_images)) {
      $compressed_images = get_post_meta($imageID, 'ic_compressed_images', true);

      // Remove Generated Images
      if (!empty($compressed_images)) {

        foreach ($compressed_images as $index => $path) {
          if (strpos($index, 'webp') !== false) {
            if (file_exists($path)) {
              unlink($path);
            }
          }
        }

      }

      $upload_dir = wp_get_upload_dir();
      $sizes = get_intermediate_image_sizes();
      foreach ($sizes as $i => $size) {
        clearstatcache();
        $image = image_get_intermediate_size($imageID, $size);
        if ($image['path']) {
          $path = $upload_dir['basedir'] . '/' . $image['path'];
          if (file_exists($path)) {
            unlink($path);
          }
        }
      }

      $path_to_image = get_attached_file($imageID);

      // Restore only full
      $restore_image_path = $backup_images['full'];

      // If backup file exists
      if (file_exists($restore_image_path)) {
        unlink($path_to_image);

        // Restore from local backups
        $copy = copy($restore_image_path, $path_to_image);

        // Delete the backup
        unlink($restore_image_path);
      }

      clearstatcache();

      wp_update_attachment_metadata($imageID, wp_generate_attachment_metadata($imageID, $path_to_image));

      delete_transient('wps_ic_compress_' . $imageID);
      delete_post_meta($imageID, 'ic_bulk_running');

      // Remove meta tags
      delete_post_meta($imageID, 'ic_stats');
      delete_post_meta($imageID, 'ic_compressed_images');
      delete_post_meta($imageID, 'ic_compressed_thumbs');
      delete_post_meta($imageID, 'ic_backup_images');
      update_post_meta($imageID, 'ic_status', 'restored');

      return true;
    }

    return false;
  }


  /**
   * @return void
   * @since v6
   */
  public function wpc_ic_start_bulk_restore()
  {
	  // Performance Lab - generate webp on upload
	  if ( function_exists( 'webp_uploads_create_sources_property' ) ) {
		  wp_send_json_error(array('msg' => 'performance-lab-compatibility'));
	  }

    // Delete previously parsed images
    delete_transient('wps_ic_bulk_done');
    delete_option('wps_ic_parsed_images');

    $local = new wps_ic_local();
    $imagesToRestore = $local->prepareRestoreImages();

    $olderBackup = false;
    if (!empty($imagesToRestore)) {
      foreach ($imagesToRestore['compressed'] as $imageID => $image) {
        $olderBackup = $this->olderBackup($imageID);
      }

      if ($olderBackup) {
        delete_option('wps_ic_parsed_images');
        delete_option('wps_ic_BulkStatus');
        delete_option('wps_ic_bulk_process');
        set_transient('wps_ic_bulk_done', true, 60);
        wp_send_json_success('older-backup');
      }
    }

    $send = $local->sendToAPI($imagesToRestore['compressed'], '', 'queueRestoreImages');

    if ($send['status'] == 'success') {
      update_option('wps_ic_bulk_process', array('date' => date('y-m-d H:i:s'), 'status' => 'restoring'));
      set_transient('wps_ic_bulk_running', date('y-m-d H:i:s'), 60 * 5);
      wp_send_json_success($send);
    } else {
      wp_send_json_error($send);
    }
  }


  /**
   * @return void
   * @since v6
   */
  public function wpc_ic_start_bulk_compress()
  {
	  // Performance Lab - generate webp on upload
	  if ( function_exists( 'webp_uploads_create_sources_property' ) ) {
		  wp_send_json_error(array('msg' => 'performance-lab-compatibility'));
	  }
    // Raise the memory and time limit
    ini_set('memory_limit', '2024M');
    ini_set('max_execution_time', '180');

    // Delete previously parsed images
    delete_transient('wps_ic_bulk_done');
    delete_option('wps_ic_parsed_images');
	  delete_option('wps_ic_bulk_counter');

    $local = new wps_ic_local();

    // It's required to set the bulk counter
    $imagesToCompress = $local->getUncompressedImages('compressing', 'bulk');

    // Send the call to API
    $send = $local->sendBulkToAPI($imagesToCompress['uncompressed']);

    if ($send['status'] == 'failed') {

      $reason = $send['reason'];

      if ($reason == 'bad-apikey') {
        $reason = 'bulk-process-bad-apikey';
      }

      wp_send_json_error(array('msg' => $reason, 'send' => print_r($send,true)));

    } elseif ($send['status'] == 'success') {
      update_option('wps_ic_bulk_process', array('date' => date('y-m-d H:i:s'), 'status' => 'compressing'));
      set_transient('wps_ic_bulk_running', date('y-m-d H:i:s'), 60 * 5);
      wp_send_json_success($send);
    } else {
      wp_send_json_error($send);
    }
  }


  public function wps_ic_remove_cname()
  {
    $cname = get_option('ic_custom_cname');
    $zone_name = get_option('ic_cdn_zone_name');
    $options = get_option(WPS_IC_OPTIONS);
    $apikey = $options['api_key'];

    delete_option('ic_cname_retry_count');

    $url = WPS_IC_KEYSURL . '?action=cdn_removecname&apikey=' . $apikey . '&cname=' . $cname . '&zone_name=' . $zone_name . '&time=' . time() . '&no_cache=' . md5(mt_rand(999, 9999));

    $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0';
    $call = wp_remote_get($url, ['timeout' => 60, 'sslverify' => false, 'user-agent' => $agent]);

    //v6 call
    $url = WPS_IC_KEYSURL . '?action=cdn_removecname_v6&apikey=' . $apikey . '&cname=' . $cname . '&zone_name=' . $zone_name . '&time=' . time() . '&no_cache=' . md5(mt_rand(999, 9999));

    $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0';
    $call = wp_remote_get($url, ['timeout' => 60, 'sslverify' => false, 'user-agent' => $agent]);


    $call = wp_remote_get(WPS_IC_KEYSURL . '?action=cdn_purge&domain=' . site_url() . '&apikey=' . $options['api_key'], ['timeout' => '10', 'sslverify' => false, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);

    delete_option('ic_custom_cname');

    $settings = get_option(WPS_IC_SETTINGS);
    $settings['cname'] = '';
    $settings['fonts'] = '';
    update_option(WPS_IC_SETTINGS, $settings);

    // Clear cache.
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }

    // Lite Speed
    if (defined('LSCWP_V')) {
      do_action('litespeed_purge_all');
    }

    // HummingBird
    if (defined('WPHB_VERSION')) {
      do_action('wphb_clear_page_cache');
    }

    if (defined('BREEZE_VERSION')) {
      global $wp_filesystem;
      require_once(ABSPATH . 'wp-admin/includes/file.php');

      WP_Filesystem();

      $cache_path = breeze_get_cache_base_path(is_network_admin(), true);
      $wp_filesystem->rmdir(untrailingslashit($cache_path), true);

      if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
      }
    }

    wp_send_json_success();
  }

  public function wps_ic_cname_retry()
  {
    $cname = get_option('ic_custom_cname');
    $retry_count = get_option('ic_cname_retry_count');

    if (!$retry_count) {
      update_option('ic_cname_retry_count', 1);
    } else {
      update_option('ic_cname_retry_count', $retry_count + 1);
    }

    if ($retry_count >= 3) {
      wp_send_json_error();
    }

    // Wait for SSL?
    sleep(10);

    wp_send_json_success(['image' => 'https://' . $cname . '/' . WPS_IC_IMAGES . '/fireworks.svg', 'configured' => 'Connected Domain: <strong>' . $cname . '</strong>']);
  }

  public function wps_ic_remove_key()
  {
    $options = get_option(WPS_IC_OPTIONS);
    $apikey = $options['api_key'];
    $site = site_url();

    delete_option('wpsShowAdvanced');

    $options['api_key'] = '';
    $options['response_key'] = '';
    $options['orp'] = '';
    $options['regExUrl'] = '';
    $options['regexpDirectories'] = '';

    // Setup URI
    $uri = WPS_IC_KEYSURL . '?action=disconnect&apikey=' . $apikey . '&site=' . urlencode($site);

    update_option(WPS_IC_OPTIONS, $options);
    wp_send_json_success();
  }

  public function wpc_ic_set_mode()
  {
    $options = new wps_ic_options();
    $preset = sanitize_text_field($_POST['value']);
    $configuration = $options->get_preset($preset);
    update_option(WPS_IC_SETTINGS, $configuration);
    wp_send_json_success($configuration);
  }

  public function wpc_ic_ajax_set_preset()
  {
    $options = new wps_ic_options;
    $preset = sanitize_text_field($_POST['value']);
    $configuration = $options->get_preset($preset);
    wp_send_json_success($configuration);
  }

  public function wps_ic_cname_add()
  {
    $zone_name = get_option('ic_cdn_zone_name');
    $options = get_option(WPS_IC_OPTIONS);
    $apikey = $options['api_key'];

    delete_option('ic_cname_retry_count');

    if (!empty($_POST['cname'])) {
      $error = '';
      $options = get_option(WPS_IC_OPTIONS);
      $apikey = $options['api_key'];

      // TODO is cname valid?
      $cname = sanitize_text_field($_POST['cname']);
      $cname = str_replace(['http://', 'https://'], '', $cname);
      $cname = rtrim($cname, '/');

      if ($zone_name == $cname) {
        $error = 'This domain is invalid, please link a new domain...';
        wp_send_json_error('invalid-domain');
      }

      if (strpos($cname, 'zapwp.com') !== false || strpos($cname, 'zapwp.net') !== false) {
        $error = 'This domain is invalid, please link a new domain...';
        wp_send_json_error('invalid-domain');
      }

      if (empty($error)) {
        if (!preg_match('/^([a-zA-z0-9\_\-]+)\.([a-zA-z0-9\_\-]+)\.([a-zA-z0-9\_\-]+)$/', $cname, $matches) && !preg_match('/^([a-zA-z0-9\_\-]+)\.([a-zA-z0-9\_\-]+)\.([a-zA-z0-9\_\-]+)\.([a-zA-z0-9\_\-]+)$/', $cname, $matches)) {
          // Subdomain is not valid
          $error = 'This domain is invalid, please link a new domain...';
          delete_option('ic_custom_cname');
          $settings = get_option(WPS_IC_SETTINGS);
          unset($settings['cname']);
          update_option(WPS_IC_SETTINGS, $settings);
          wp_send_json_error('invalid-domain');
        } else {
          // Verify CNAME DNS
          $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0';
          $verify_cname_dns = 'https://frankfurt.zapwp.net/?dnsCheck=true&host=' . $cname . '&zoneName=' . $zone_name . '&random=' . microtime(true);

          $call = wp_remote_get($verify_cname_dns, ['timeout' => 60, 'sslverify' => false, 'user-agent' => $agent]);
          if (wp_remote_retrieve_response_code($call) == 200) {
            $body = wp_remote_retrieve_body($call);
            $body = json_decode($body, true);
            $data = $body['data'];
            $host = $data['host'];
            $recordsType = $data['records']['type'];
            $recordsTarget = $data['records']['target'];


            if ($recordsType == 'CNAME') {
              if ($recordsTarget == $zone_name) {
                update_option('ic_custom_cname', sanitize_text_field($cname));

                $url = WPS_IC_KEYSURL . '?action=cdn_setcname&apikey=' . $apikey . '&cname=' . $cname . '&zone_name=' . $zone_name . '&time=' . time() . '&no_cache=' . md5(mt_rand(999, 9999));

                $call = wp_remote_get($url, ['timeout' => 120, 'sslverify' => false, 'user-agent' => $agent]);
                sleep(5);

                //v6 call:
                $url = WPS_IC_KEYSURL . '?action=cdn_setcname_v6&apikey=' . $apikey . '&cname=' . $cname . '&zone_name=' . $zone_name . '&time=' . time() . '&no_cache=' . md5(mt_rand(999, 9999));

                $call = wp_remote_get($url, ['timeout' => 120, 'sslverify' => false, 'user-agent' => $agent]);
                sleep(5);

                $call = wp_remote_get(WPS_IC_KEYSURL . '?action=cdn_purge&domain=' . site_url() . '&apikey=' . $options['api_key'], ['timeout' => '10', 'sslverify' => false, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0']);

                // Wait for SSL?
                sleep(6);

                wp_send_json_success(['image' => 'https://' . $cname . '/' . WPS_IC_IMAGES . '/fireworks.svg', 'configured' => 'Connected Domain: <strong>' . $cname . '</strong>']);
              }
            }

            wp_send_json_error('invalid-dns-prop');
          } else {
            wp_send_json_error('dns-api-not-working');
          }
        }
      }

      $custom_cname = get_option('ic_custom_cname');
      if (!$custom_cname) {
        $custom_cname = '';
      }

      wp_send_json_success($custom_cname);
    } else {
      $custom_cname = delete_option('ic_custom_cname');

      wp_send_json_success();
    }
  }

  public function wps_ic_exclude_list()
  {
    $excludeList = $_POST['excludeList'];
    $lazyExcludeList = $_POST['lazyExcludeList'];
    $delayExcludeList = $_POST['delayExcludeList'];

    if (!empty($excludeList)) {
      $excludeList = rtrim($excludeList, "\n");
      $excludeList = explode("\n", $excludeList);
      update_option('wpc-ic-external-url-exclude', $excludeList);
    } else {
      delete_option('wpc-ic-external-url-exclude');
    }

    if (!empty($lazyExcludeList)) {
      $lazyExcludeList = rtrim($lazyExcludeList, "\n");
      $lazyExcludeList = explode("\n", $lazyExcludeList);
      update_option('wpc-ic-lazy-exclude', $lazyExcludeList);
    } else {
      delete_option('wpc-ic-lazy-exclude');
    }

    if (!empty($delayExcludeList)) {
      $delayExcludeList = rtrim($delayExcludeList, "\n");
      $delayExcludeList = explode("\n", $delayExcludeList);
      update_option('wpc-ic-delay-js-exclude', $delayExcludeList);
    } else {
      delete_option('wpc-ic-delay-js-exclude');
    }

    wp_send_json_success();
  }

  public function wps_ic_geolocation_force()
  {
    global $wps_ic;

    $post = $_POST['location'];

    if ($post == 'Automatic') {
      $geolocation = $this->geoLocateAjax();
      wp_send_json_success($geolocation);
    }

    $location_data = ['server' => 'frankfurt.zapwp.net', 'continent' => 'EU', 'continent_name' => 'Europe', 'country' => 'DE', 'country_name' => 'Germany'];

    switch ($post) {
      case 'EU':
        break;
      case 'US':
        $location_data = ['server' => 'nyc.zapwp.net', 'continent' => 'US', 'continent_name' => 'United States', 'country' => 'US', 'country_name' => 'United States'];
        break;
      case 'OC':
        $location_data = ['server' => 'sydney.zapwp.net', 'continent' => 'OC', 'continent_name' => 'Oceania', 'country' => 'AU', 'country_name' => 'Australia'];
        break;
      case 'AS':
        $location_data = ['server' => 'singapore.zapwp.net', 'continent' => 'AS', 'continent_name' => 'Asia', 'country' => 'Singapore', 'country_name' => 'Singapore'];
        break;
    }

    update_option('wpc-ic-force-location', $location_data);
    update_option('wps_ic_geo_locate', $location_data);

    wp_send_json_success($location_data);
  }

  public function wps_ic_geolocation()
  {
    global $wps_ic;
    $geolocation = $this->geoLocateAjax();
    wp_send_json_success($geolocation);
  }

  public function wps_ic_RestoreFinished()
  {
    global $wps_ic;

    $count = $_POST['count'] . ' of ' . $_POST['count'];
    $output = '';

    $output .= '<div class="wps-ic-bulk-html-wrapper">';

    $output .= '<div class="bulk-restore-container">';

    $output .= '<div class="bulk-restore-preview-container">';
    $output .= '<div class="bulk-restore-preview-inner">';
    $output .= '<div class="bulk-restore-preview-image-holder">';
    $output .= '<img src="' . WPS_IC_URI . 'assets/images/bulk/restore-completed-image_opt.png' . '">';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="bulk-restore-info">';

    $output .= '<div class="bulk-restore-status-top-left">';
    $output .= '<img src="' . WPS_IC_URI . 'assets/images/shield.svg' . '">';
    $output .= '<span class="badge">';
    $output .= '<i class="icon-check"></i> Restored';
    $output .= '</span>';
    $output .= '</div>';

    $output .= '<div class="bulk-restore-status-top-right">';
    $output .= '<h3>' . $count . '</h3>';
    $output .= '<h5>Images Restored</h5>';
    $output .= '</div>';

    $output .= '<div class="bulk-restore-status-container">';
    $output .= '<h4>Image Restore Complete!</h4>';
    $output .= '<span>We have successfully restored all of your images.</span>';
    $output .= '<div class="bulk-status-progress-bar">
              <div class="progress-bar-outer">
                <div class="progress-bar-inner" style="width: 100%;"></div>
              </div>
            </div>';
    $output .= '</div>';

    $output .= '</div>';

    $output .= '</div>';

    wp_send_json_success(['html' => $output]);
  }

  public function wps_ic_doBulkRestore()
  {
    global $wps_ic;

    $lastProgress = $_POST['lastProgress'];
    $bulkStats = get_transient('wps_ic_bulk_stats');
    $compressed_images_queue = get_transient('wps_ic_restore_queue');

    if (empty($bulkStats['images_restored'])) {
      $bulkStats['images_restored'] = 0;
    }

    if ($compressed_images_queue['queue']) {
      $attID = $compressed_images_queue['queue'][0];

      // First Image
      set_transient('wps_ic_restore_' . $attID, ['imageID' => $attID, 'status' => 'restoring'], 0);

      // do the restore
      self::$local->restore($attID);

      set_transient('wps_ic_restore_' . $attID, ['imageID' => $attID, 'status' => 'restored'], 0);

      unset($compressed_images_queue['queue'][0]);
      $compressed_images_queue['queue'] = array_values($compressed_images_queue['queue']);

      // Sleep so that it takes longer
      sleep(2);

      /**
       * Calculate Progress
       */
      $leftover_images = count($compressed_images_queue['queue']);
      $total_images = $compressed_images_queue['total_images'];
      $done_images = $total_images - $leftover_images;
      $progress_percent = round(($done_images / $total_images) * 100);

      // Bulk Stats
      $bulkStats['images_restored'] += 1;

      set_transient('wps_ic_bulk_stats', $bulkStats, 1800);
      set_transient('wps_ic_restore_queue', $compressed_images_queue, 1800);

      wp_send_json_success(['done' => $attID, 'progress' => $progress_percent, 'finished' => $done_images, 'leftover' => $leftover_images, 'total' => $total_images, 'todo' => $compressed_images_queue, 'html' => $this->bulkRestoreHtml($attID, $lastProgress)]);
    }

    wp_send_json_error();
  }

  public function bulkRestoreHtml($imageID, $lastProgress = '')
  {
    $output = '';

    $thumbnail = $full = wp_get_attachment_image_src($imageID, 'full');

    $image_full_filename = basename($full[0]);
    $filedata = get_attached_file($imageID);

    $originalPath = wp_get_original_image_path($imageID);
    $original_filesize = filesize($originalPath);

    $output .= '<div class="wps-ic-bulk-html-wrapper">';

    $output .= '<div class="bulk-restore-container">';

    $output .= '<div class="bulk-restore-preview-container">';
    $output .= '<div class="bulk-restore-preview-inner">';
    $output .= '<div class="bulk-restore-preview-image-holder">';
    $output .= '<div class="image-holder-inner">';
    $output .= '<div style="background-image:url(' . $thumbnail[0] . ');" class="image-holder-bg"></div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="bulk-restore-info">';

    $output .= '<div class="bulk-restore-status-top-left">';
    $output .= '<span class="badge"><i class="icon icon-check"></i> Restored</span>';
    $output .= '</div>';

    $output .= '<div class="bulk-restore-status-top-right">';
    $output .= '<h3>16 / 16</h3>';
    $output .= '<h5>Images Restored</h5>';
    $output .= '</div>';

    $output .= '<div class="bulk-restore-status-container">';
    $output .= '<h4>' . $image_full_filename . '</h4>';
    $output .= '<span><i class="restore-bullet"></i> ' . wps_ic_format_bytes($original_filesize, null, null, false) . '</span>';
    $output .= '<div class="bulk-status-progress-bar">
              <div class="progress-bar-outer">
                <div class="progress-bar-inner" style="width: ' . $lastProgress . '%;"></div>
              </div>
            </div>';
    $output .= '</div>';

    $output .= '</div>';

    $output .= '</div>';

    $output .= '</div>';

    return $output;
  }


  public function wps_ic_media_library_bulk_heartbeat()
  {
    global $wpdb, $wps_ic;
    $heartbeat_query = $wpdb->get_results("SELECT * FROM " . $wpdb->options . " WHERE option_name LIKE '_transient_wps_ic_compress_%' OR option_name LIKE '_transient_wps_ic_restore_%'");

    $html = array();
    if ($heartbeat_query) {
      foreach ($heartbeat_query as $heartbeat_item) {
        $value = unserialize(untrailingslashit($heartbeat_item->option_value));

        if ($value['status'] == 'compressed' || $value['status'] == 'restored') {
          $html[$value['imageID']] = $wps_ic->media_library->compress_details($value['imageID']);
          delete_transient('wps_ic_compress_' . $value['imageID']);
          delete_transient('wps_ic_restore_' . $value['imageID']);
        }
      }

      wp_send_json_success($html);
    }

    wp_send_json_error();
  }


  public function do_restore($arg)
  {
    $stats = get_post_meta($arg, 'ic_status', true);
    if (!empty($stats) && $stats == 'restored') {
      wp_send_json_success('already-restored');
    }

    set_transient('wps_ic_restore_' . $arg, ['imageID' => $arg, 'status' => 'restoring'], 0);

    // do the restore
    self::$local->restore($arg);

    set_transient('wps_ic_restore_' . $arg, ['imageID' => $arg, 'status' => 'restored'], 0);
    die();
  }

  

  /**
   * Live Compress
   */
  public function wps_ic_restore_live()
  {
	  // Performance Lab - generate webp on upload
	  if ( function_exists( 'webp_uploads_create_sources_property' ) ) {
		  wp_send_json_error(array('msg' => 'performance-lab-compatibility'));
	  }


    // do the restore
    self::$local->restoreV4($_POST['attachment_id']);

    sleep(1);
    wp_send_json_success();
  }


  public function wps_ic_compress_live()
  {
// Performance Lab - generate webp on upload
	  if ( function_exists( 'webp_uploads_create_sources_property' ) ) {
		  wp_send_json_error(array('msg' => 'performance-lab-compatibility'));
	  }
    self::$accountStatus = parent::getAccountStatusMemory();

    $stats = get_post_meta($_POST['attachment_id'], 'ic_status', true);
    if (!empty($stats) && $stats == 'compressed') {
      wp_send_json_error(array('msg' => 'file-already-compressed'));
    }

    if (defined('WPS_IC_LOCAL_V') && WPS_IC_LOCAL_V == 3) {
      self::$local->singleCompressV3($_POST['attachment_id']);
    } else if (defined('WPS_IC_LOCAL_V') && WPS_IC_LOCAL_V == 4) {
	    self::$local->singleCompressV4($_POST['attachment_id']);
    } else {
      $settings = get_option(WPS_IC_SETTINGS);
      $return = self::$local->compress_image($_POST['attachment_id'], false, $settings['retina'], $settings['generate_webp']);
    }

    sleep(1);
    wp_send_json_success();
  }


  /**
   * Count Uncompressed Images
   */
  public function wps_ic_count_uncompressed_images()
  {
    global $wpdb;

    $args = ['post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'meta_query' => ['relation' => 'AND', ['key' => 'wps_ic_data', 'compare' => 'NOT EXISTS'], ['key' => 'wps_ic_exclude', 'compare' => 'NOT EXISTS']]];

    $uncompressed_attachments = new WP_Query($args);
    $total_file_size = 0;
    if ($uncompressed_attachments->have_posts()) {
      while ($uncompressed_attachments->have_posts()) {
        $uncompressed_attachments->the_post();
        $postID = get_the_ID();

        $filesize = filesize(get_attached_file($postID));
        $total_file_size += $filesize;
      }
    }

    wp_send_json_success(['uncompressed' => $total_file_size, 'unit' => 'Bytes']);
  }

	public function wps_ic_save_mode(){
		$preset = sanitize_text_field($_POST['mode']);
		$cdn = sanitize_text_field($_POST['cdn']);
		$options = new wps_ic_options();
		$settings = $options->get_preset($preset);

		if ($cdn == 'true'){
			$settings['live-cdn']               = '1';
			$settings['serve']                  = [
				'jpg'   => '1',
				'png'   => '1',
				'gif'   => '1',
				'svg'   => '1',
				'css'   => '1',
				'js'    => '1',
				'fonts' => '0'
			];
			$settings['css']                    = 1;
			$settings['js']                     = 1;
			$settings['fonts']                  = 0;
			$settings['generate_adaptive']      = 1;
			$settings['generate_webp']          = 1;
			$settings['retina']                 = 1;
		}
    else {
			$settings['live-cdn']               = '0';
			$settings['serve']                  = [
				'jpg'   => '0',
				'png'   => '0',
				'gif'   => '0',
				'svg'   => '0',
				'css'   => '0',
				'js'    => '0',
				'fonts' => '0'
			];
			$settings['css']                    = 0;
			$settings['js']                     = 0;
			$settings['fonts']                  = 0;
			$settings['generate_adaptive']      = 0;
			$settings['generate_webp']          = 0;
			$settings['retina']                 = 0;
		}


    $wpc_excludes = get_option('wpc-inline');
    $wpc_excludes['inline_js'] = explode(',',"jquery.min,adaptive,jquery-migrate,wp-includes");
    update_option('wpc-inline',$wpc_excludes);

    $wpc_excludes = get_option('wpc-excludes');
    $wpc_excludes['delay_js'] = array();
    update_option('wpc-excludes',$wpc_excludes);


		update_option(WPS_IC_SETTINGS, $settings);
		update_option(WPS_IC_PRESET, $preset);

    // Preload Page
    $cacheLogic = new wps_ic_cache();

    // Remove generateCriticalCSS Options
    delete_option('wps_ic_gen_hp_url');

    if ($preset == 'safe') {
      // TODO: MAYBE WP CACHE?!
      // Setup Advanced Caching
      $htaccess = new wps_ic_htaccess();
      $htaccess->removeHtaccessRules();
      $htaccess->removeAdvancedCache();
      $htaccess->setWPCache(false);
    } else {
      // Setup Advanced Caching
      $htaccess = new wps_ic_htaccess();
      // Add WP_CACHE to wp-config.php
      $htaccess->setWPCache(true);
      $htaccess->setAdvancedCache();
    }

    // Remove & Purge Cache Files for home directory (that's all pages)
    $cacheLogic::removeHtmlCacheFiles(0);

    // Preload the home page only
    $cacheLogic::preloadPage(0);

		wp_send_json_success();
	}


}