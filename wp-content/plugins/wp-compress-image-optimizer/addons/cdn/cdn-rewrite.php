<?php
include 'rewriteLogic.php';
include WPS_IC_DIR . 'addons/minify/html.php';
include_once WPS_IC_DIR . 'addons/cache/cacheHtml.php';
include WPS_IC_DIR . 'addons/criticalCss/criticalCss.php';

class wps_cdn_rewrite
{

  public static $settings;
  public static $options;
  public static $lazy_excluded_list;
  public static $excluded_list;
  public static $default_excluded_list;
  public static $cdnEnabled;
  public static $preloaderAPI;
  public static $excludes_class;

  public static $assets_to_preload;
  public static $assets_to_defer;

  public static $emoji_remove;

  public static $isAjax;
  public static $brizyCache;
  public static $brizyActive;

  // Regexp Url & Dirs
  public static $regExURL;
  public static $regExDir;
  public static $findImages;

  // Predefined API URLs
  public static $apiUrl;
  public static $apiAssetUrl;

  // Site URL, Upload Dir
  public static $updir;
  public static $home_url;
  public static $site_url;
  public static $site_url_scheme;

  // SVG Placeholder (empty svg)
  public static $svg_placeholder;


  // CSS / JS Variables
  public static $excludes;
  public static $fonts;
  public static $css;
  public static $css_img_url;
  public static $css_minify;
  public static $js;
  public static $js_minify;

  // Image Compress Variables
  public static $replaceAllLinks;
  public static $external_url_excluded;
  public static $externalUrlEnabled;
  public static $zone_test;
  public static $zone_name;
  public static $is_retina;
  public static $exif;
  public static $webp;
  public static $retina_enabled;
  public static $adaptive_enabled;
  public static $webp_enabled;
  public static $lazy_enabled;
  public static $native_lazy_enabled;
  public static $sizes;
  public static $randomHash;
  public static $is_multisite;
  public static $keys;

  //Overrides
  public static $delay_js_override;
  public static $defer_js_override;
  public static $lazy_override;

  public static $rewriteLogic;
  public static $minifyHtml;
  public static $cacheHtml;
  public static $criticalCss;
  private static $isAmp;

  public function __construct()
  {
  }

  public static function local()
  {
    global $ic_running;

    if (is_admin() || strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
      return true;
    }

    if (!empty($_GET['ignore_ic'])) {
      return true;
    }

    $options = get_option(WPS_IC_OPTIONS);
    $response_key = $options['response_key'];
    if (empty($response_key)) {
      return true;
    }

    self::$isAjax = (function_exists("wp_doing_ajax") && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX);

    // Don't run in admin side!
    if (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") {
      return true;
    }

    // TODO: Check this for wpadmin and frontend ajax
    if (!self::$isAjax) {
      if (wp_is_json_request() || is_admin() || !empty($_GET['trp-edit-translation']) || !empty($_GET['elementor-preview']) || !empty($_GET['preview']) || !empty($_GET['PageSpeed']) || (!empty($_GET['fl_builder']) || isset($_GET['fl_builder'])) || !empty($_GET['et_fb']) || !empty($_GET['tatsu']) || !empty($_GET['tve']) || !empty($_GET['ct_builder']) || !empty($_GET['fb-edit']) || (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") || (!empty($_GET['page']) && $_GET['page'] == 'livecomposer_editor')) {
        return true;
      }
    }

    if (!is_admin()) {
      if (self::$settings['css'] == 0 && self::$settings['js'] == 0 && self::$settings['serve']['jpg'] == 0 && self::$settings['serve']['png'] == 0 && self::$settings['serve']['gif'] == 0 && self::$settings['serve']['svg'] == 0) {
        self::buffer_local_go();
      }
    }
  }

  public function buffer_local_go()
  {
    if (self::$isAjax) {
      $wps_ic_cdn = new wps_cdn_rewrite();
    }

    ob_start([$this, 'buffer_local_callback']);
  }

  public static function init()
  {
    global $ic_running;

    if (strpos($_SERVER['REQUEST_URI'], '.xml') !== false) {
      return true;
    }

    if (is_admin() || strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
      return true;
    }

    if ($ic_running) {
      return true;
    }

    $ic_running = true;

    if (!empty($_GET['ignore_cdn']) || !empty($_GET['ignore_ic'])) {
      return true;
    }

    $options = get_option(WPS_IC_OPTIONS);
    $response_key = $options['response_key'];
    if (empty($response_key)) {
      return true;
    }

    if (self::$settings['css'] == 0 && self::$settings['js'] == 0 && self::$settings['serve']['jpg'] == 0 && self::$settings['serve']['png'] == 0 && self::$settings['serve']['gif'] == 0 && self::$settings['serve']['svg'] == 0) {
      return true;
    }

    self::$isAjax = (function_exists("wp_doing_ajax") && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX);

    // Don't run in admin side!
    if (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") {
      return true;
    }

    // TODO: Check this for wpadmin and frontend ajax
    if (!self::$isAjax) {
      if (wp_is_json_request() || is_admin() || !empty($_GET['trp-edit-translation']) || !empty($_GET['elementor-preview']) || !empty($_GET['preview']) || !empty($_GET['PageSpeed']) || (!empty($_GET['fl_builder']) || isset($_GET['fl_builder'])) || !empty($_GET['et_fb']) || !empty($_GET['tatsu']) || !empty($_GET['tve']) || !empty($_GET['fb-edit']) || !empty($_GET['ct_builder']) || (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") || (!empty($_GET['page']) && $_GET['page'] == 'livecomposer_editor')) {
        return true;
      }
    }

    add_filter('get_site_icon_url', ['wps_cdn_rewrite', 'favicon_replace'], 10, 1);
  }

  public static function favicon_replace($url)
  {
    if (empty($url)) {
      return $url;
    }

    if (strpos($url, self::$zone_name) !== false) {
      return $url;
    }

    $url = 'https://' . self::$zone_name . '/m:0/a:' . self::reformat_url($url);

    return $url;
  }

  public static function reformat_url($url, $remove_site_url = false)
  {
    $url = trim($url);

    if (strpos($url, 'login') !== false) {
      return $url;
    }

    // Check if url is maybe a relative URL (no http or https)
    if (strpos($url, 'http') === false) {
      // Check if url is maybe absolute but without http/s
      if (strpos($url, '//') === 0) {
        // Just needs http/s
        $url = 'https:' . $url;
      } else {
        $url = str_replace('../wp-content', 'wp-content', $url);
        //if we replace all we break things like '.../wp-content/cache/min/1/wp-content/...'
        $url_replace = preg_replace('/\/wp-content/', 'wp-content', $url, 1);
        $url = self::$site_url;
        $url = rtrim($url, '/');
        $url .= '/' . $url_replace;
      }
    }

    $formatted_url = $url;

    if (strpos($formatted_url, '?brizy_media') === false) {
      $formatted_url = explode('?', $formatted_url);
      $formatted_url = $formatted_url[0];
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'log_url_format') {
      $fp = fopen(WPS_IC_DIR . 'url_Format.txt', 'a+');
      fwrite($fp, 'URL: ' . $formatted_url . "\r\n");
      fwrite($fp, 'Site URL: ' . self::$site_url . "\r\n");
      fwrite($fp, 'Slashes: ' . addcslashes(self::$site_url, '/') . "\r\n");
      fwrite($fp, '---' . "\r\n");
      fclose($fp);
    }

    if ($remove_site_url) {
      $formatted_url = str_replace(self::$site_url, '', $formatted_url);
      $formatted_url = str_replace(str_replace(['https://', 'http://'], '', self::$site_url), '', $formatted_url);
      $formatted_url = str_replace(addcslashes(self::$site_url, '/'), '', $formatted_url);
      $formatted_url = ltrim($formatted_url, '\/');
      $formatted_url = ltrim($formatted_url, '/');
    }

    if (!empty($_GET['dbg_reformaturl'])) {
      return print_r(array($url, $formatted_url), true);
    }

    if (!empty(self::$cdnEnabled) && self::$cdnEnabled == '1') {
      if (self::$randomHash == 0 && strpos($formatted_url, '.css') !== false) {
        $formatted_url .= '?icv=' . WPS_IC_HASH;
      }

      if (self::$randomHash == 0 && strpos($formatted_url, '.js') !== false) {
        $formatted_url .= '?js_icv=' . WPS_IC_JS_HASH;
      }

      if (self::$randomHash != 0) {
        return $formatted_url . '?icv_random=' . self::$randomHash;
      }
    }

    return $formatted_url;
  }


  function add_scripts_inline($tag, $handle, $src)
  {
    if (strpos(strtolower($src), 'webpack') !== false) {
      return $tag;
    }

    // TODO: Hrvoje
    if (strpos(strtolower($src), 'tweenmax') !== false) {
      $urlGet = false;
      // TODO: Move to default defers
      $check = wp_http_validate_url($src);
      if ($check || strpos($src, '//') === 0) {
        if (strpos($src, 'http') === false) {
          $src = 'https:' . $src;
        }
        $urlGet = true;
        $url = $src;
      } else {
        $url = get_home_url() . $src;
      }

      if ($urlGet) {
        $tag = '<script type="text/javascript" class="wps-inline" id="tweenmax-js">'. $this->get_script_content_url($url) . '</script>';
      } else {
        $tag = '<script type="text/javascript" class="wps-inline" id="tweenmax-js">' . $this->get_script_content($url) . '</script>';
      }
      return $tag;
    }

    if (empty($this->inline_js) || !is_array($this->inline_js)) {
      $this->inline_js = array();
    }

    $found = false;
    foreach ($this->inline_js as $k => $inlineJs) {
      if (strpos(strtolower($src), $inlineJs) !== false) {
        $found = true;
        break;
      }
    }

    if ($found) {
      global $wp_scripts;

      $check = wp_http_validate_url($src);
      if ($check || strpos($src, '//') === 0) {
        $url = $src;
      } else {
        $url = get_home_url() . $src;
      }

      $tag = '';
      if (!empty($wp_scripts->registered[$handle]->extra['before'][1])) {
        $tag .= '<script type="text/javascript" id="' . $handle . '-js-before">' . $wp_scripts->registered[$handle]->extra['before'][1] . '</script>';
      }

      // TODO: Make more elegant
      if (strpos($handle, 'awesome') !== false) {
        $tag .= '<script type="text/javascript" defer class="wps-inline" id="' . $handle . '-js">' . $this->get_script_content($url) . '</script>';
      } else {
        $tag .= '<script type="text/javascript" class="wps-inline" id="' . $handle . '-js">' . $this->get_script_content($url) . '</script>';
      }

      if (!empty($wp_scripts->registered[$handle]->extra['after'][1])) {
        $tag .= '<script type="text/javascript" id="' . $handle . '-js-after">' . $wp_scripts->registered[$handle]->extra['after'][1] . '</script>';
      }
    }

    return $tag;
  }

  public function get_script_content_url($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
    return $data;
  }
  public function get_script_content($url)
  {
    //
    //    $ch = curl_init();
    //    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //    curl_setopt($ch, CURLOPT_URL, $url);
    //    $data = curl_exec($ch);
    //    curl_close($ch);

    $relativePath = wp_make_link_relative($url);
    $path = ltrim($relativePath, '/');

    //check if is folder install and if folder is in url remove it (it is already in ABSPATH)
    $last_abspath = basename(ABSPATH);
    $first_path = explode('/', $path)[0];
    if ($last_abspath == $first_path) {
      $path = substr($path, strlen($first_path));
      $path = ltrim($path, '/');
    }

    $content = file_get_contents(ABSPATH . $path);

    // Remove comments
    $jsCode = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);

    return $jsCode;
  }


  public function dnsPrefetch()
  {
    if (strlen(trim(self::$zone_name)) > 0) {
      if (!empty($_GET['dbg']) && $_GET['dbg'] == 'direct') {
        if (!empty($_GET['custom_server'])) {
          self::$zone_name = $_GET['custom_server'] . '/key:' . self::$options['api_key'];
          echo '<link rel="dns-prefetch" href="//' . $_GET['custom_server'] . '" />';
        }
      } else {
        echo '<link rel="dns-prefetch" href="//' . self::$zone_name . '" />';
      }
    }
  }

  public function deferJSAssets($tag, $handle, $src)
  {
    return $tag;
  }

  public function rewrite_script_tag($tag, $handle, $src)
  {

    $src = trim($src);

    if (self::isExcludedFrom('cdn', $src)) {
      return $tag;
    }

    if ($this->defaultExcluded($src)) {
      return $tag;
    }

    if (self::is_excluded_link($src)) {
      return $tag;
    }

    /**
     * TODO:
     * check if external is enabled
     */
    if ((self::$externalUrlEnabled == '0' || empty(self::$externalUrlEnabled))) {
      if (!self::image_url_matching_site_url($src)) {
        // External not enabled
        return $tag;
      }
    }

    if (self::$externalUrlEnabled == '1' && !self::image_url_matching_site_url($src)) {
      // External not enabled
      if (strpos($src, self::$zone_name) === false) {
        if (strpos($src, 'http') === false) {
          $src = ltrim($src, '//');
          $src = 'https://' . $src;
        }

        if (!self::is_excluded_link($src)) {
          $src = 'https://' . self::$zone_name . '/m:0/a:' . $src;
        }
      }
    }

    if (self::$cdnEnabled == '1' && self::$js == '1') {
      if (strpos($src, self::$zone_name) === false) {
        $fileMinify = self::$js_minify;
        if (self::isExcluded('js_minify', $src)) {
          $fileMinify = '0';
        }

        /**
         * Is script inside Wp-content or Wp-includes
         */
        if (strpos($src, 'wp-content') !== false || strpos($src, 'wp-includes') !== false) {
          $src = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($src, false);
        } else {
          $src = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($src, false);
        }
      }

      if (!empty(self::$settings['js_defer'])) {
        if (self::$settings['js_defer'] == '1' && !self::$defer_js_override) {
          foreach (self::$assets_to_defer as $i => $defer_key) {
            if (strpos($tag, $defer_key) !== false) {
              if (!self::isExcluded('defer_js', $src) && !strpos($src, 'slide')) {
                $tag = '<script type="text/javascript" src="' . $src . '" defer></script>';
              }
            }
          }
        } else {
          $tag = preg_replace('/src=["|\'](.*?)["|\']/si', 'src="' . $src . '"', $tag);
        }
      } else {

        if (strpos($src, 'gtag') !== false) {
          $tag = '<script type="text/javascript" src="' . $src . '" defer></script>';
        }

        if (strpos($src, 'fontawesome') !== false) {
          $tag = '<script type="text/javascript" src="' . $src . '" defer></script>';
          return $tag;
        }

        $tag = preg_replace('/src=["|\'](.*?)["|\']/si', 'src="' . $src . '"', $tag);
      }

      return $tag;
    }

    return $tag;
  }

  public function defaultExcluded($string)
  {
    foreach (self::$default_excluded_list as $i => $excluded_string) {
      if (strpos($string, $excluded_string) !== false) {
        return true;
      }
    }

    return false;
  }

  public static function is_excluded_link($link)
  {
    /**
     * Is the link in excluded list?
     */
    if (empty($link)) {
      return false;
    }

    if (strpos($link, '.css') !== false || strpos($link, '.js') !== false) {
      foreach (self::$default_excluded_list as $i => $excluded_string) {
        if (strpos($link, $excluded_string) !== false) {
          return true;
        }
      }
    }

    if (!empty(self::$excluded_list)) {
      foreach (self::$excluded_list as $i => $value) {
        if (strpos($link, $value) !== false) {
          // Link is excluded
          return true;
        }
      }
    }

    return false;
  }

  public static function isExcludedFrom($setting, $link)
  {

    if (isset(self::$excludes[$setting])) {
      $excludeList = self::$excludes[$setting];
      if (!empty($excludeList)) {
        foreach ($excludeList as $key => $value) {
          if (strpos($link, $value) !== false && $value != '') {
            return true;
          }
        }
      }
    }


    return false;
  }

  /**
   * Is link matching the site url?
   *
   * @param $image
   *
   * @return bool
   */
  public static function image_url_matching_site_url($image)
  {
    $site_url = self::$site_url;
    $image = str_replace(['https://', 'http://'], '', $image);
    $site_url = str_replace(['https://', 'http://'], '', $site_url);

    if (strpos($image, '.css') !== false || strpos($image, '.js') !== false) {
      foreach (self::$default_excluded_list as $i => $excluded_string) {
        if (strpos($image, $excluded_string) !== false) {
          return false;
        }
      }
    }

    if (strpos($image, $site_url) === false) {
      // Image not on site
      return false;
    } else {
      // Image on site
      return true;
    }
  }

  public static function isExcluded($setting, $link)
  {
    if (isset(self::$excludes[$setting])) {
      $excludeList = self::$excludes[$setting];
      if (!empty($excludeList)) {
        foreach ($excludeList as $key => $value) {
          if (strpos($link, $value) !== false && $value != '') {
            return true;
          }
        }
      }
    }


    return false;
  }

  public function crittr_style_tag($html, $handle, $href, $media)
  {

    if (strpos($href, self::$site_url) === false) {

    } else {
      $cdnHref = WPS_IC_URI . 'fixCss.php?zoneName=' . self::$zone_name . '&css=' . urlencode($href) . '&rand=' . time();
      $html = str_replace($href, $cdnHref, $html);
    }

    return $html;
  }

  public function adjust_style_tag($html, $handle, $href, $media)
  {
    if (!empty(self::$settings['remove-render-blocking']) && self::$settings['remove-render-blocking'] == '1') {
      foreach (self::$assets_to_preload as $i => $preload_key) {
        if (self::$excludes_class->strInArray($html, self::$excludes_class->renderBlockingCSSExcludes())) {
          return $html;
        }
        if (strpos($href, $preload_key) !== false) {
          if (strpos($html, 'preload') == false) {
            if (strpos($html, 'rel=') !== false) {
              // Rel exists, change it
              $html = preg_replace('/rel\=["|\'](.*?)["|\']/', 'rel="preload" as="style" onload="this.rel=\'stylesheet\'" ', $html);
            } else {
              // Rel does not exist, create it
              $html = str_replace('/>', 'rel="preload" as="style" onload="this.rel=\'stylesheet\'"/>', $html);
            }
          }

          return $html;
        }

      }
    }

    if (strpos($href, 'wp-includes/css/dist/block-library') !== false) {
      if (!empty($this::$settings['disable-gutenberg']) && $this::$settings['disable-gutenberg'] == '1') {
        return '';
      }
    }

    return $html;
  }

  public function strInArray($haystack, $needles = [])
  {

    if (empty($needles)) {
      return false;
    }

    $haystack = strtolower($haystack);

    foreach ($needles as $needle) {
      $needle = strtolower(trim($needle));

      $res = strpos($haystack, $needle);
      if ($res !== false) {
        return true;
      }
    }

    return false;
  }

  public function adjust_src_url($src)
  {

    $src = trim($src);

    if (strpos($src, '.css') !== false && empty(self::$css) || self::$css == '0') {
      return $src;
    } elseif (strpos($src, '.js') !== false && empty(self::$js) || self::$js == '0') {
      return $src;
    }

    if (self::isExcludedFrom('cdn', $src)) {
      return $src;
    }

    if ($this->defaultExcluded($src)) {
      return $src;
    }

    if (self::is_excluded_link($src)) {
      return $src;
    }

    /**
     * TODO:
     * check if external is enabled
     */
    if ((self::$externalUrlEnabled == '0' || empty(self::$externalUrlEnabled))) {
      if (!self::image_url_matching_site_url($src)) {
        // External not enabled
        return $src;
      }
    }

    if (self::$externalUrlEnabled == '1' && !self::image_url_matching_site_url($src)) {
      // External not enabled
      if (strpos($src, self::$zone_name) === false) {
        if (strpos($src, 'http') === false) {
          $src = ltrim($src, '//');
          $src = 'https://' . $src;
        }

        if (!self::is_excluded_link($src)) {
          $src = 'https://' . self::$zone_name . '/m:0/a:' . $src;
        }
      }

      return $src;
    }

    if (strpos($src, self::$zone_name) === false) {
      if (strpos($src, '.css') !== false) {
        $fileMinify = self::$css_minify;
        if (self::isExcluded('css_minify', $src)) {
          $fileMinify = '0';
        }

        if (!self::is_excluded_link($src)) {
          if (self::$css_img_url == '1') {
            $src = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($src);
          } else {
            if (strpos($src, 'wp-content') !== false || strpos($src, 'wp-includes') !== false) {
              $src = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($src, false);
            } else {
              $src = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($src, false);
            }
          }
        }
      } elseif (strpos($src, '.js') !== false) {
        $fileMinify = self::$js_minify;
        if (self::isExcluded('js_minify', $src)) {
          $fileMinify = '0';
        }

        if (strpos($src, 'wp-content') !== false || strpos($src, 'wp-includes') !== false) {
          $src = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($src, false);
        } else {
          $src = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($src, false);
        }
      }
    }

    return $src;
  }

  public function buffer_local_callback($html)
  {
    if (empty($this->criticalCombine) || !isset($this->criticalCombine)) {
      $this->criticalCombine = false;
    }

    //Do something with the buffer (HTML)
    if (isset($_GET['brizy-edit-iframe']) || isset($_GET['brizy-edit']) || isset($_GET['preview'])) {
      return $html;
    }

    if (self::$isAjax) {
      return $html;
    }

    if (is_admin() || is_feed() || !empty($_GET['trp-edit-translation']) || !empty($_GET['elementor-preview']) || !empty($_GET['preview']) || !empty($_GET['PageSpeed']) || !empty($_GET['tve']) || !empty($_GET['et_fb']) || (!empty($_GET['fl_builder']) || isset($_GET['fl_builder'])) || !empty($_GET['ct_builder']) || !empty
      ($_GET['tatsu']) || !empty($_GET['fb-edit']) || !empty($_GET['bricks']) || (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") || (!empty($_GET['page']) && $_GET['page'] == 'livecomposer_editor')) {
      return $html;
    }


    if (self::$cdnEnabled == 0) {
      $htmlBefore = $html;
      $html = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/si', [$this, 'local_script_encode'], $html);

      if (empty($html)) {
        $html = $htmlBefore;
      }

      $html = preg_replace_callback('/(?<![\"|\'])<img[^>]*>/i', [$this, 'local_image_tags'], $html);

      if (self::$fonts == 1) {
        $html = self::$rewriteLogic->fonts($html);
      }

      $html = preg_replace_callback('/\[script\-wpc\](.*?)\[\/script\-wpc\]/i', [$this, 'local_script_decode'], $html);

      //Combine JS
      if ($this->doCacheCombine() && (isset(self::$settings['js_combine']) && self::$settings['js_combine'] == '1')) {
        $combine_js = new wps_ic_combine_js();
        $html = $combine_js->maybe_do_combine($html);
      }

      if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'combine_css') {
        return $html;
      }

      //Combine CSS
      if (($this->doCacheCombine() && (isset(self::$settings['css_combine']) && self::$settings['css_combine'] == '1')) || $this->criticalCombine) {
        if (empty($_GET['stopCombineCSS'])) {
          $combine_css = new wps_ic_combine_css();
          $html = $combine_css->maybe_do_combine($html);
        }
      }

    }

    //Delay JS
    if (empty($_GET['disableDelay'])) {
      if (!empty(self::$settings['delay-js']) && self::$settings['delay-js'] == '1' && !self::$delay_js_override && !self::$preloaderAPI) {
        $js_delay = new wps_ic_js_delay();
        $html = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/si', [$js_delay, 'delay_script_replace'], $html);
      }
    }

    // Cache
    if ((!empty(self::$settings['cache']['advanced']) && self::$settings['cache']['advanced'] == '1')) {
      if (!self::isExcludedFromCache($html) && $this->doCacheCombine()) {
        if (!self::is_mobile()) {
          return self::$cacheHtml->saveCache($html);
        } else {
          return self::$cacheHtml->saveCache($html, 'mobile');
        }
      }
    }

    return $html;
  }

  public function doCacheCombine()
  {
    if (is_404()) {
      return false;
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'direct') {
      return false;
    }

    if (!empty($_GET['forceRecombine']) && $_GET['forceRecombine'] == 'true') {
      return true;
    }

	  $allowed_params = self::$keys->get_allowed_params();
	  $get_keys = array_keys($_GET);

	  sort($allowed_params);
	  sort($get_keys);

	  if ($allowed_params === $get_keys) {
			return true;
	  }

    if (!empty($_GET)) {
      return false;
    }

    if (current_user_can('manage_options')) {
      return false;
    }

    if (self::dontRunif()) {
      return true;
    }

    if ($this->isPageBuilder()) {
      return false;
    }

    if ($this->isPageBuilderFE()) {
      return false;
    }

    if ($this->isFEBuilder()) {
      return false;
    }

    if ($this->isAPICall()) {
      return false;
    }

    if (wp_doing_cron()) {
      return false;
    }


    return true;
  }

  public static function dontRunif()
  {
    // is url excluded?
		if (!empty(self::$settings['exclude-url-from-all']) && self::$settings['exclude-url-from-all'] == 1) {
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$url = explode('?', $url);
			$url = $url[0];
			$url_excludes = get_option( 'wpc-url-excludes' );
			if ( ! empty( $url_excludes['exclude-url-from-all'] ) && in_array( $url, $url_excludes['exclude-url-from-all'] )
			) {
				return false;
			}
		}
    
    // Any hide login plugins active?
    if (self::hiddenAdminArea()) {
      return false;
    }

    //WP User Frontend check
    if (class_exists('WP_User_Frontend')) {
      $content = get_post_field('post_content', get_the_ID());

      // Check if the content contains wpuf shorcode
      if (preg_match('/\[wpuf/', $content)) {
        return false;
      }
    }

    if (self::MediaActions()) {
      return false;
    }

    if (defined('DOING_AUTOSAVE')) {
      return;
    }

    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'cornerstone') !== false) {
      return false;
    }

	  if (!empty($_POST['_cs_nonce'])) { //cornerstone
		  return false;
	  }

    if (is_admin() || strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
      return false;
    }

    if (!empty($_SERVER['REQUEST_URI'])) {
      if (strpos($_SERVER['REQUEST_URI'], 'wp-json') || strpos($_SERVER['REQUEST_URI'], 'rest_route')) {
        return false;
      }
    }

    if (isset($_GET['brizy-edit-iframe']) || isset($_GET['brizy-edit']) || isset($_GET['preview'])) {
      return false;
    }

    if (!empty($_GET['page']) && $_GET['page'] == 'bwc') {
      return false;
    }


    if (!empty($_GET['trp-edit-translation']) || !empty($_GET['bwc']) || !empty($_GET['fb-edit']) || !empty($_GET['bricks']) || !empty($_GET['elementor-preview']) || !empty($_GET['PageSpeed']) || (!empty($_GET['fl_builder']) || isset($_GET['fl_builder'])) || !empty($_GET['et_fb']) || !empty($_GET['tatsu']) || !empty($_GET['tatsu-header']) || !empty($_GET['tatsu-footer']) || !empty($_GET['tve']) || !empty
      ($_GET['ct_builder']) || (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") || (!empty($_GET['page']) && $_GET['page'] == 'livecomposer_editor')) {
      return false;
    }

	  if (!empty($_POST['pp_action'])){
		  //power pack for beaver builder ajax get posts fix
		  return false;
	  }

	  if (!empty($_GET['wc-ajax']) && $_GET['wc-ajax'] == 'get_refreshed_fragments'){
		  return false;
	  }

    return true;
  }

  public static function hiddenAdminArea()
  {

    // AIOS
    if (class_exists('AIO_WP_Security')) {
      // Hide Login Exists
      $configs = get_option('aio_wp_security_configs');
      if (!empty($configs['aiowps_login_page_slug'])) {
        if (strpos($_SERVER['REQUEST_URI'], $configs['aiowps_login_page_slug']) !== false) {
          return true;
        }
      }
    }

    // WPS Hide Login
    if (class_exists('WPS\WPS_Hide_Login\Plugin')) {
      // Hide Login Exists
      $loginPage = get_option('whl_page');
      if (!empty($loginPage)) {
        if (strpos($_SERVER['REQUEST_URI'], '/' . $loginPage) !== false) {
          return true;
        }
      }
    }

    // Hide My WP - Ghost
    if (class_exists('HMWP_Classes_ObjController')) {
      $option = get_option('hmwp_options');

      if (!empty($option)) {
        $option = json_decode($option, true);
        $loginPage = $option['hmwp_login_url'];
        if (!empty($loginPage)) {
          if (strpos($_SERVER['REQUEST_URI'], $loginPage) !== false) {
            return true;
          }
        }
      }
    }

  }

  public static function MediaActions()
  {
    if (!empty($_GET['preloadCache'])) {
      return true;
    }

    if (!empty($_GET['getAllImages'])) {
      return true;
    }

    if (!empty($_POST['getImageByID']) || !empty($_GET['getImageByID'])) {
      return true;
    }

    if (!empty($_POST['deliverSingleImage']) || !empty($_GET['deliverSingleImage'])) {
      return true;
    }

    if (!empty($_POST['deliverImages']) || !empty($_GET['deliverImages'])) {
      return true;
    }

    if (!empty($_POST['restoreImages']) || !empty($_GET['restoreImages'])) {
      return true;
    }
  }

  /**
   * FrontEnd Editors Detection for various page builders
   * @return bool
   */
  public static function isPageBuilder()
  {
    $page_builders = ['run_compress', //wpc
      'run_restore', //wpc
      'elementor-preview', //elementor
      'fl_builder', //beaver builder
      'et_fb', //divi
      'preview', //WP Preview
      'builder', //builder
      'brizy', //brizy
      'fb-edit', //avada
      'bricks', //bricks
      'ct_template', //ct_template
      'ct_builder', //ct_builder
      'cs-render', //cornerstone
      'tatsu', //tatsu
      'trp-edit-translation', //thrive
      'brizy-edit-iframe', //brizy
      'ct_builder', //oxygen
      'livecomposer_editor', //livecomposer
      'tatsu', //tatsu
      'tatsu-header', //tatsu-header
      'tatsu-footer', //tatsu-footer
      'tve' //thrive
    ];

    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'cornerstone') !== false) {
      return true;
    }

    foreach ($page_builders as $page_builder) {
      if (isset($_GET[$page_builder])) {
        return true;
      }
    }

    return false;
  }

  /**
   * FrontEnd Editors Detection for various page builders
   * @return bool
   */
  public static function isPageBuilderFE()
  {
    if (class_exists('BT_BB_Root')) {
      if (is_user_logged_in() && !is_admin()) {
        return true;
      }
    }

    return false;
  }

  public static function isFEBuilder()
  {
    if (!empty($_GET['trp-edit-translation']) || !empty($_GET['elementor-preview']) || !empty($_GET['tatsu']) || !empty($_GET['preview']) || !empty($_GET['PageSpeed']) || !empty($_GET['tve']) || !empty($_GET['et_fb']) || (!empty($_GET['fl_builder']) || isset($_GET['fl_builder'])) || !empty($_GET['ct_builder']) || !empty($_GET['fb-edit']) || !empty($_GET['bricks']) || !empty($_GET['brizy-edit-iframe']) || !empty($_GET['brizy-edit']) || (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") || (!empty($_GET['page']) && $_GET['page'] == 'livecomposer_editor')) {
      return true;
    } else {
      return false;
    }
  }

  public function isAPICall()
  {
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
      if (strpos($_SERVER['HTTP_USER_AGENT'], 'Compress-API') !== false) {
        return true;
      }
    }

    return false;
  }

  public static function isExcludedFromCache($html)
  {
    $output = [];

    if ((strpos($html, 'id="wp-admin-bar') !== false || strpos($html, "id='wp-admin-bar") !== false) ||
      (strpos($html, 'id="wpadminbar"') !== false || strpos($html, "id='wpadminbar'") !== false)) {
      return true;
    }

    if (!is_array(self::$excludes['cache'])) {
      $excludedUrls = explode("\n", self::$excludes['cache']);
    } else {
      $excludedUrls = self::$excludes['cache'];
    }

    if (!empty($excludedUrls)) {
      foreach ($excludedUrls as $k => $path) {
        if (!empty($path)) {
          $path = trim($path);
          if (strpos($_SERVER['REQUEST_URI'], $path) !== false) {
            return true;
          }
        }
      }
    }

    // Is Woo commerce Cart
    if (function_exists('is_woocommerce') && is_woocommerce() && is_cart()) {
      return true;
    }

    if (!is_array(self::$settings['wpc-excludes']['cache'])) {
      $excludedUrls = explode("\n", self::$settings['wpc-excludes']['cache']);
    } else {
      $excludedUrls = self::$excludes['cache'];
    }

    if (!empty($excludedUrls)) {
      foreach ($excludedUrls as $k => $path) {
        if (!empty($path)) {
          $path = trim($path);
          if (strpos($_SERVER['REQUEST_URI'], $path) !== false) {
            return true;
          }
        }
      }
    }

    return false;
  }

  public function is_mobile()
  {
    if (!empty($_GET['simulate_mobile'])) {
      return true;
    }

    if (isset($_SERVER['HTTP_USER_AGENT']) && (preg_match('#^.*(2.0\ MMP|240x320|400X240|AvantGo|BlackBerry|Blazer|Cellphone|Danger|DoCoMo|Elaine/3.0|EudoraWeb|Googlebot-Mobile|hiptop|IEMobile|KYOCERA/WX310K|LG/U990|MIDP-2.|MMEF20|MOT-V|NetFront|Newt|Nintendo\ Wii|Nitro|Nokia|Opera\ Mini|Palm|PlayStation\ Portable|portalmmm|Proxinet|ProxiNet|SHARP-TQ-GX10|SHG-i900|Small|SonyEricsson|Symbian\ OS|SymbianOS|TS21i-10|UP.Browser|UP.Link|webOS|Windows\ CE|WinWAP|YahooSeeker/M1A1-R2D2|iPhone|iPod|Android|BlackBerry9530|LG-TU915\ Obigo|LGE\ VX|webOS|Nokia5800).*#i', $_SERVER['HTTP_USER_AGENT']) || preg_match('#^(w3c\ |w3c-|acs-|alav|alca|amoi|audi|avan|benq|bird|blac|blaz|brew|cell|cldc|cmd-|dang|doco|eric|hipt|htc_|inno|ipaq|ipod|jigs|kddi|keji|leno|lg-c|lg-d|lg-g|lge-|lg/u|maui|maxo|midp|mits|mmef|mobi|mot-|moto|mwbp|nec-|newt|noki|palm|pana|pant|phil|play|port|prox|qwap|sage|sams|sany|sch-|sec-|send|seri|sgh-|shar|sie-|siem|smal|smar|sony|sph-|symb|t-mo|teli|tim-|tosh|tsm-|upg1|upsi|vk-v|voda|wap-|wapa|wapi|wapp|wapr|webc|winw|winw|xda\ |xda-).*#i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4)))) {
      return true;
    }

    return false;
  }


  public function checkCache()
  {
    if (!empty($_GET['disableCache']) || !empty($_GET['forceRecombine'])) {
      return true;
    }

    if (!empty($_GET['dbg_checkCache'])) {
      die('Check cache');
    }

    if (self::dontRunif()) {
      /**
       * Check for cache first
       */

      if (!empty($_GET['dontRunCache'])) {
        die('Check cache 23');
      }

      $isUserLoggedIn = is_user_logged_in();
      if ($isUserLoggedIn) return;

      $cache = new wps_cacheHtml();

      if ($cache->cacheEnabled()) {

        if (!empty($_GET['cacheDbg2'])) {
          die('x');
        }

        $mobile = self::is_mobile();
        $prefix = '';
        if ($mobile) $prefix = 'mobile';
        if ($cache->cacheExists($prefix)) {
          $isCacheExpired = false;

          // Not required as get cache sorts this
          $isCacheValid = true;

          if (!$isCacheExpired && $isCacheValid) {
            $cache->getCache($prefix);
            die();
          }

        }
      }

    }
  }

  public function buffer_callback_v3()
  {

    if (is_admin()) {
      return true;
    }

    if (!empty($_GET['buffer_callback'])) {
      echo 'asd';
      die();
    }

    // Is an ajax request?
    self::$isAjax = (function_exists("wp_doing_ajax") && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX);

    // TODO: Check this for wpadmin and frontend ajax
    if (!self::$isAjax) {
      if (is_admin() || !empty($_GET['trp-edit-translation']) || (!empty($_GET['fl_builder']) || isset($_GET['fl_builder'])) || !empty($_GET['elementor-preview']) || !empty($_GET['preview']) || !empty($_GET['PageSpeed']) || !empty($_GET['et_fb']) || !empty($_GET['tve']) || !empty($_GET['tatsu']) || !empty($_GET['ct_builder']) || !empty($_GET['fb-edit']) || (!empty($_GET['builder']) && !empty($_GET['builder_id'])) || !empty($_GET['bricks']) || (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") || (!empty($_GET['page']) && $_GET['page'] == 'livecomposer_editor')) {
        return true;
      }

      if (!empty($_GET['tatsu']) || !empty($_GET['tatsu-header']) || !empty($_GET['tatsu-footer'])) {
        return true;
      }
    }

    $init = $this->mainInit();

    if (!self::$cdnEnabled) {
      return true;
    }

    if (isset($post->post_type) && strpos($post->post_type, 'wfocu') !== false) {
      // Ignore Post Types
    } else {
      if (!empty($_GET['generateCritical'])) {
        if (!empty(self::$settings['critical']['css']) && self::$settings['critical']['css'] == '1') {
          self::$criticalCss->sendCriticalUrl();
        }
      }

      if (empty($_GET['wpc_no_buffer'])) {
        ob_start([$this, 'cdn_rewriter']);
      }
    }
  }

  public function mainInit()
  {
    // Raise memory limit
    ini_set('memory_limit', '1024M');

    if (is_admin()) {
      return;
    }

    self::$options = get_option(WPS_IC_OPTIONS);

    if (!isset(self::$options['api_key']) || empty(self::$options['api_key'])) {
      return;
    }

    self::$excludes = get_option('wpc-excludes');
    self::$excludes_class = new wps_ic_excludes();
    self::$isAmp = new wps_ic_amp();
    self::$preloaderAPI = 0;

    self::$settings = get_option(WPS_IC_SETTINGS);
    $this->compatibility = new wps_ic_compatibility();
    self::$settings = $this->compatibility->check(self::$settings);

    if (self::$isAmp->isAmp()) {
      self::$lazy_enabled = '0';
      self::$adaptive_enabled = '0';
      self::$retina_enabled = '0';
      self::$settings['delay_js'] = '0';
      self::$settings['inline-js'] = '0';
    }

    $this->criticalCombine = false;
    if (!empty($_GET['criticalCombine']) && $_GET['criticalCombine'] == 'true') {
      $this->criticalCombine = true;
      self::$settings['critical']['css'] = 0;
    }

    if (!empty($_GET['forceRecombine']) && $_GET['forceRecombine'] == 'true') {
      $post_id = get_the_ID();
      $cache = new wps_ic_cache();
      $cache->update_css_hash($post_id);
      $cache->removeHtmlCacheFiles($post_id);
    }

    self::$findImages = '';
    if (!empty(self::$settings['serve']['jpg']) && self::$settings['serve']['jpg'] == '1') {
      self::$findImages .= 'jpg|jpeg|';
    }

    if (!empty(self::$settings['serve']['png']) && self::$settings['serve']['png'] == '1') {
      self::$findImages .= 'png|';
    }

    if (!empty(self::$settings['serve']['gif']) && self::$settings['serve']['gif'] == '1') {
      self::$findImages .= 'gif|';
    }

    if (!empty(self::$settings['serve']['svg']) && self::$settings['serve']['svg'] == '1') {
      self::$findImages .= 'svg|';
    }

    self::$keys = new wps_ic_url_key();

    self::$findImages .= 'webp|';

    self::$findImages = rtrim(self::$findImages, '|');

    // Plugin is NOT Activated
    $response_key = self::$options['api_key'];
    if (empty($response_key)) {
      return;
    }

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'PreloaderAPI') !== false || !empty($_GET['dbg_preload'])) {
      self::$preloaderAPI = 1;
    }

    self::$zone_test = 0;
    self::$is_multisite = is_multisite();

    self::$randomHash = 0;

    self::$rewriteLogic = new wps_rewriteLogic();
    self::$minifyHtml = new wps_minifyHtml();
    self::$cacheHtml = new wps_cacheHtml();
    self::$criticalCss = new wps_criticalCss();

    //Add files inline
	  if (self::dontRunif()) {
		  $inline_scripts = get_option( 'wpc-inline' );
		  if ( ! empty( $inline_scripts['inline_js'] ) ) {
			  $this->inline_js = $inline_scripts['inline_js'];
		  }
		  if ( ! empty( $inline_scripts['inline_css'] ) ) {
			  $this->inline_css = $inline_scripts['inline_css'];
		  }

		  if ( ! empty( self::$settings['inline-js'] ) && self::$settings['inline-js'] == 1 ) {
			  if ( ! empty( $this->inline_js ) ) {
				  foreach ( $this->inline_js as $key => $script ) {
					  if ( substr( $script, - 3 ) == '-js' ) {
						  $this->inline_js[ $key ] = substr( $script, 0, - 3 );
					  }
				  }
			  }
			  add_filter( 'script_loader_tag', [ $this, 'add_scripts_inline' ], PHP_INT_MAX, 3 );
		  }
	  }

	  //Perfmatters settings check
    //$this->perfMattersOverride();

    //Rocket settings check
    //$this->rocketOverride();

	  $integrations = new wps_ic_integrations();
	  self::$defer_js_override = $integrations->is_override_active('defer_js');
	  self::$delay_js_override = $integrations->is_override_active('delay_js');
	  self::$lazy_override = $integrations->is_override_active('lazy');

    // default excluded keywords
    self::$default_excluded_list = ['redditstatic', 'ai-uncode', 'gtm', 'instagram.com', 'fbcdn.net', 'twitter', 'google', 'coinbase', 'cookie', 'schema', 'recaptcha', 'data:image', 'stats.jpg'];

    // Preload anything inside themes,elementor,wp-includes
    self::$assets_to_preload = ['themes', 'elementor', 'wp-includes', 'google'];
    self::$assets_to_defer = ['themes', 'tracking', 'fontawesome'];

    if (!empty($_GET['ignore_ic'])) {
      return;
    }

    if (!empty($_GET['randomHash'])) {
      self::$randomHash = time();
    }

    if (strpos($_SERVER['REQUEST_URI'], '.xml') !== false) {
      return;
    }

    if (empty(self::$options['css_hash'])) {
      self::$options['css_hash'] = 5021;
    }

    if (empty(self::$options['js_hash'])) {
      self::$options['js_hash'] = 5021;
    }

    if (!defined('WPS_IC_HASH')) {
      define('WPS_IC_HASH', self::$options['css_hash']);
    }

    if (!defined('WPS_IC_JS_HASH')) {
      define('WPS_IC_JS_HASH', self::$options['js_hash']);
    }

    if (!empty(self::$excludes['delay_js'])) {
      $this->delay_js_exclude = self::$excludes['delay_js'];
    } else {
      $this->delay_js_exclude = '';
    }

    self::$cdnEnabled = self::$settings['live-cdn'];
    if (self::$settings['css'] == 0 && self::$settings['js'] == 0 && self::$settings['serve']['jpg'] == 0 && self::$settings['serve']['png'] == 0 && self::$settings['serve']['gif'] == 0 && self::$settings['serve']['svg'] == 0) {
      self::$cdnEnabled = 0;
    }

    // Is an ajax request?
    self::$isAjax = (function_exists("wp_doing_ajax") && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX);

    // Don't run in admin side!
    if (!empty($_SERVER['SCRIPT_URL']) && $_SERVER['SCRIPT_URL'] == "/wp-admin/customize.php") {
      return;
    }

    self::$svg_placeholder = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAwIiBoZWlnaHQ9IjEwMCI+PHBhdGggZD0iTTIgMmgxMDAwdjEwMEgyeiIgZmlsbD0iI2ZmZiIgb3BhY2l0eT0iMCIvPjwvc3ZnPg==';

    self::$updir = wp_upload_dir();

    if (!is_multisite()) {
      self::$site_url = site_url();
      self::$home_url = home_url();
    } else {
      $current_blog_id = get_current_blog_id();
      switch_to_blog($current_blog_id);

      self::$site_url = network_site_url();
      self::$home_url = home_url();
    }

    self::$site_url_scheme = parse_url(self::$site_url, PHP_URL_SCHEME);

    self::$lazy_excluded_list = get_option('wpc-ic-lazy-exclude');
    self::$excluded_list = get_option('wpc-ic-external-url-exclude');

    if (!is_array(self::$excluded_list)) {
      self::$external_url_excluded = explode("\n", self::$excluded_list);
    } else {
      self::$external_url_excluded = self::$excluded_list;
    }

    if (defined('BRIZY_VERSION')) {
      self::$brizyCache = get_option('wps_ic_brizy_cache');
      self::$brizyActive = true;
    } else {
      self::$brizyActive = false;
    }

    $custom_cname = get_option('ic_custom_cname');

    if (empty($custom_cname) || !$custom_cname) {
      self::$zone_name = get_option('ic_cdn_zone_name');
    } else {
      self::$zone_name = $custom_cname;
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'direct') {
      if (!empty($_GET['custom_server'])) {
        self::$zone_name = $_GET['custom_server'] . '/key:' . self::$options['api_key'];
      }
    }

    if (empty(self::$zone_name)) {
      return;
    }

    self::$is_retina = '0';
    self::$webp = '0';
    self::$externalUrlEnabled = 'false';

    self::$lazy_enabled = self::$settings['lazy'];
    self::$native_lazy_enabled = self::$settings['nativeLazy'];
    self::$adaptive_enabled = self::$settings['generate_adaptive'];
    self::$webp_enabled = self::$settings['generate_webp'];
    self::$retina_enabled = self::$settings['retina'];
    if (!empty(self::$settings['replace-all-link'])) {
      self::$replaceAllLinks = self::$settings['replace-all-link'];
    } else {
      self::$replaceAllLinks = '0';
    }

    if (!empty($_GET['disableLazy'])) {
      self::$lazy_enabled = '0';
      self::$native_lazy_enabled = '0';
    }

    if (!empty(self::$settings['external-url'])) {
      self::$externalUrlEnabled = self::$settings['external-url'];
    }

    if (empty(self::$settings['emoji-remove'])) {
      self::$settings['emoji-remove'] = 0;
    }

    if (empty(self::$settings['external-url'])) {
      self::$settings['external-url'] = 0;
    }

    if (empty(self::$settings['css'])) {
      self::$settings['css'] = 0;
    }

    if (empty(self::$settings['fonts'])) {
      self::$settings['fonts'] = 0;
    }

    if (empty(self::$settings['js'])) {
      self::$settings['js'] = 0;
    }

    if (empty(self::$settings['preserve_exif'])) {
      self::$settings['preserve_exif'] = 0;
    }

    if (!empty($_GET['ic_override_setting']) && $_GET['ic_override_setting'] == 'lazy') {
      self::$lazy_enabled = (bool)$_GET['value'];
    }

    if (!empty($_GET['ic_lazy'])) {
      self::$lazy_enabled = (bool)$_GET['ic_lazy'];
      self::$settings['css'] = 1;
      self::$settings['js'] = 1;
    }

    if (!empty($_GET['css'])) {
      self::$settings['css'] = (bool)$_GET['css'];
    }

    if (!empty($_GET['js'])) {
      self::$settings['js'] = (bool)$_GET['js'];
    }

    if (empty(self::$settings['css_image_urls']) || !isset(self::$settings['css_image_urls'])) {
      self::$settings['css_image_urls'] = '0';
    }

    if (!empty(self::$settings['minify-css']) && self::$settings['minify-css']) {
      self::$settings['minify-css'] = '1';
    } else {
      self::$settings['minify-css'] = '0';
    }

    if (!empty(self::$settings['minify-js']) && self::$settings['minify-js']) {
      self::$settings['minify-js'] = '1';
    } else {
      self::$settings['minify-js'] = '0';
    }

    self::$externalUrlEnabled = self::$settings['external-url'];
    self::$css = self::$settings['css'];
    self::$css_img_url = self::$settings['css_image_urls'];
    self::$css_minify = self::$settings['css_minify'];
    self::$js = self::$settings['js'];
    self::$js_minify = self::$settings['js_minify'];
    self::$emoji_remove = self::$settings['emoji-remove'];
    self::$exif = self::$settings['preserve_exif'];
    self::$fonts = self::$settings['fonts'];

    // If Optimization Quality is Not set...
    if (empty(self::$settings['optimization']) || self::$settings['optimization'] == '' || self::$settings['optimization'] == '0') {
      self::$settings['optimization'] = 'i';
    }

    if (!empty(self::$retina_enabled) && self::$retina_enabled == '1') {
      if (isset($_COOKIE["ic_pixel_ratio"])) {
        if ($_COOKIE["ic_pixel_ratio"] >= 2) {
          self::$is_retina = '1';
        }
      }
    }

    if (!empty(self::$webp_enabled) && self::$webp_enabled == '1') {
      self::$webp = '1';

      if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') && !strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
        self::$webp_enabled = false;
        self::$webp = '0';
      }
    }

    if (!empty($_GET['test_zone'])) {
      if ($_GET['test_zone'] == 'cdn-rage4') {
        self::$zone_test = 1;
        self::$zone_name = $_GET['server'] . '.zapwp.net/key:' . self::$options['api_key'];
      } else {
        self::$zone_name = $_GET['test_zone'] . '.wpmediacompress.com/key:' . self::$options['api_key'];
      }
    }

    if (!empty(self::$exif) && self::$exif == '1') {
      self::$apiUrl = 'https://' . self::$zone_name . '/q:' . self::$settings['optimization'] . '/e:1';
    } else {
      self::$apiUrl = 'https://' . self::$zone_name . '/q:' . self::$settings['optimization'];
    }

    self::$apiAssetUrl = 'https://' . self::$zone_name . '/a:';

    if (self::$preloaderAPI) {
      global $post;
      self::$lazy_enabled = '0';
      self::$native_lazy_enabled = '0';
      self::$adaptive_enabled = '0';
      self::$retina_enabled = '0';
      self::$settings['remove-render-blocking'] = 0;
      $preloaded_pages = get_option('wpc-ic-preloaded-pages');
      //check if page is preloaded, and add to list if not
      if (is_array($preloaded_pages) && !in_array($post->ID, $preloaded_pages)) {
        array_push($preloaded_pages, $post->ID);
        update_option('wpc-ic-preloaded-pages', $preloaded_pages);
      } else if ($preloaded_pages === false) {
        update_option('wpc-ic-preloaded-pages', [$post->ID]);
      }
    }

    if (!empty($_GET['overwrite_retina'])) {
      self::$retina_enabled = '1';
      self::$is_retina = '1';
    }

    if (!empty($_GET['debugCritical']) || !empty($_GET['generateCriticalAPI'])) {
      add_filter('style_loader_tag', [&$this, 'crittr_style_tag'], 10, 4);
    }

    if (self::$settings['css'] == 0 && self::$settings['js'] == 0 && self::$settings['serve']['jpg'] == 0 && self::$settings['serve']['png'] == 0 && self::$settings['serve']['gif'] == 0 && self::$settings['serve']['svg'] == 0) {
      self::$cdnEnabled = 0;
    }

    if (self::$cdnEnabled == 1) {
      if (self::dontRunif()) {

        if (self::$css == "1") {
          add_filter('style_loader_src', array(&$this, 'adjust_src_url'), 10, 2);
          add_filter('style_loader_tag', [&$this, 'adjust_style_tag'], 10, 4);
        }

        if (self::$js == "1") {
          add_filter('script_loader_tag', [&$this, 'rewrite_script_tag'], 10, 3);
        }

        add_filter('script_loader_tag', [&$this, 'deferJSAssets'], 10, 3);

      }

      add_action("wp_head", [&$this, 'dnsPrefetch'], 0);
    } else {
      // Local Mode
      if (self::dontRunif()) {
        if (self::$css == "1") {
          add_filter('style_loader_src', [&$this, 'adjust_src_url'], 10, 2);
          add_filter('style_loader_tag', [&$this, 'adjust_style_tag'], 10, 4);
        }

        if (self::$js == "1") {
          add_filter('script_loader_src', [&$this, 'adjust_src_url'], 10, 2);
        }
      }

      if (self::$js == "1" || self::$css == "1") {
        add_action("wp_head", [&$this, 'dnsPrefetch'], 0);
      }
    }
  }

  public function perfMattersOverride()
  {
    if (function_exists('perfmatters_version_check')) {
      $perfmatters_options = get_option('perfmatters_options');

      if (!empty($perfmatters_options['assets']['delay_js']) && $perfmatters_options['assets']['delay_js']) {
        self::$delay_js_override = 1;
      }

      if (!empty($perfmatters_options['assets']['defer_js']) && $perfmatters_options['assets']['defer_js']) {
        self::$defer_js_override = 1;
      }

      if (!empty($perfmatters_options['lazyload']['lazy_loading']) && $perfmatters_options['lazyload']['lazy_loading']) {
        self::$lazy_override = 1;
      }
    }
  }

  public function rocketOverride()
  {
    if (function_exists('get_rocket_option')) {
      $rocket_settings = get_option('wp_rocket_settings');

      if ($rocket_settings['delay_js']) {
        self::$delay_js_override = 1;
      }

      if ($rocket_settings['defer_all_js']) {
        self::$defer_js_override = 1;
      }

      if ($rocket_settings['lazyload']) {
        self::$lazy_override = 1;
      }
    }
  }

  public function script_encode($html)
  {
    $html = base64_encode($html[0]);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_encode') {
      return print_r([$html], true);
    }

    return '[script-wpc]' . $html . '[/script-wpc]';
  }

  public function script_decode($html)
  {
    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_decode') {
      return print_r([$html], true);
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'no_decode') {
      return $html[1];
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'after_base64_replace') {
      return $html[1];
    }

    $html = base64_decode($html[1]);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'after_base64_decode') {
      return $html;
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_decode_after') {
      return print_r([str_replace('<iframe', 'framea', $html)], true);
    }

    return $html;
  }

  public function noscript_encode($html)
  {
    $html = base64_encode($html[0]);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_encode') {
      return print_r([$html], true);
    }

    return '[noscript-wpc]' . $html . '[/noscript-wpc]';
  }

  public function noscript_decode($html)
  {
    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_decode') {
      return print_r([$html], true);
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'no_decode') {
      return $html[1];
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'after_base64_replace') {
      return $html[1];
    }

    $html = base64_decode($html[1]);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'after_base64_decode') {
      return $html;
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_decode_after') {
      return print_r([str_replace('<iframe', 'framea', $html)], true);
    }

    return $html;
  }

  public function jetsmart_ajax_rewrite($args)
  {
    $html = $args['content'];

    //Prep Site URL
    $escapedSiteURL = quotemeta(self::$home_url);
    $regExURL = '(https?:|)' . substr($escapedSiteURL, strpos($escapedSiteURL, '//'));

    //Prep Included Directories
    $directories = 'wp\-content|wp\-includes';
    if (!empty($cdn['cdn_directories'])) {
      $directoriesArray = array_map('trim', explode(',', $cdn['cdn_directories']));

      if (count($directoriesArray) > 0) {
        $directories = implode('|', array_map('quotemeta', array_filter($directoriesArray)));
      }
    }

    $old_values['lazy'] = self::$lazy_enabled;
    $old_values['adaptive'] = self::$adaptive_enabled;

    self::$lazy_enabled = 0;
    self::$adaptive_enabled = 0;

    $regEx = '#(?<=[(\"\'])(?:' . $regExURL . ')?/(?:((?:' . $directories . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';
    $html = preg_replace_callback($regEx, [$this, 'cdn_rewrite_url'], $html, true);

    self::$lazy_enabled = $old_values['lazy'];
    self::$adaptive_enabled = $old_values['adaptive'];

    $args['content'] = $html;

    return $args;
  }


  public function cdn_rewriter($html)
  {
    $isUserLoggedIn = is_user_logged_in();
    $isVisitorMode = false;
    if (!empty($_GET['wpc_visitor_mode']) && $_GET['wpc_visitor_mode'] == true) {
      $isVisitorMode = $_GET['wpc_visitor_mode'];
    }

    $criticalCombine = false;
    if (!empty($_GET['criticalCombine']) && $_GET['criticalCombine'] == true) {
      $criticalCombine = true;
    }

    if (!empty($_GET['no_rewriter'])) {
      return 'no-cdn-rewriter';
    }

    if (!empty($_GET['ignore_ic']) || !self::dontRunif()) {
      return $html;
    }

    /**
     * Woocommerce fix - store stops working
     */
    if (isset($_GET['wc-ajax']) || isset($_GET['product_sku']) || !empty($_POST['product_sku'])) {
      return $html;
    }

    if (is_feed()) {
      return $html;
    }

    if (self::$isAjax) {
      return $html;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'xmlrpc') !== false || strpos($_SERVER['REQUEST_URI'], 'wp-json') !== false) {
      return $html;
    }

    self::$isAmp = new wps_ic_amp();

    if (self::$isAmp->isAmp()) {
      self::$lazy_enabled = '0';
      self::$adaptive_enabled = '0';
      self::$retina_enabled = '0';
      self::$settings['delay_js'] = '0';
      self::$settings['inline-js'] = '0';
    }


    // This is for AJAX Replace, works on Jet Engine and some others - might need integration
    // TODO: Integration for other ajax loaders
    if (!empty($_POST['action'])) {
      // Find all URLs on page that have not been replaced
      $html = preg_replace_callback('/(?<![\"|\'])<img[^>]*>/i', [self::$rewriteLogic, 'replaceImageTagsDoSlash'], $html);
      return $html;
    }

    //clear html comments (so combine doesn't pick them up)
	  $html = preg_replace_callback("/<!--(.*?)-->/ms", function($matches) {
		  if (strpos($matches[1], 'sc_project') !== false) {
				// statcounter puts some of their needed JS inside html comments
			  return $matches[0];
		  } else {
			  return '';
		  }
	  }, $html);

    //Prep Site URL
    $this->getRegexp();

    // TODO: Možda treba za nitro?
    //    if (is_null(self::$rewriteLogic)) {
    //      self::$rewriteLogic = new wps_rewriteLogic();
    //    }

    $html = self::$rewriteLogic->scriptContent($html);

    // Layzload Iframe - sets load="lazy" to iframe tag
    // TODO: Fix so that it checks does iframe already have load="lazy|auto"
    if (!empty(self::$settings['iframe-lazy']) && self::$settings['iframe-lazy'] == '1') {
      $html = preg_replace_callback('/<iframe(.*?)><\/iframe>/si', [$this, 'replace_iframe_tags'], $html);
    }

    $html = self::$rewriteLogic->encodeIframe($html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'crittr_replace_css') {
      return $html;
    }

    if ((!empty($_GET['debugCritical']) || !empty($_GET['generateCriticalAPI']))) {
      $isUserLoggedIn = is_user_logged_in();
      $html = preg_replace_callback('/<link\b[^>]*>/si', [$this, 'crittr_replace_css'], $html);
    }

    $html = preg_replace_callback('/<noscript><iframe.*?<\/noscript>/is', [$this, 'noscript_encode'], $html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'backgroundSizing') {
      return $html;
    }

    // Replace Background
    if (!empty(self::$settings['background-sizing']) && self::$settings['background-sizing'] == '1') {
      $html = self::$rewriteLogic->backgroundSizing($html);
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'replaceImageTags') {
      return $html;
    }

    // TODO: Hrvoje, Fix and improve
    if (!empty(self::$settings['delay_js']) && self::$settings['delay_js'] == '1') {
      $html = self::$rewriteLogic->defferGtag($html);
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'replaceImageTags0') {
      return $html;
    }

    $html = self::$rewriteLogic->defferFontAwesome($html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'replaceImageTags1') {
      return $html;
    }

    // Replace <img> tags
    $html = self::$rewriteLogic->replaceImageTags($html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'replaceImageTags2') {
      return $html;
    }

    // Replace <picture> tags
    $html = self::$rewriteLogic->replacePictureTags($html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'replaceImageTags3') {
      return $html;
    }

    // Find revSlider Data-thumb
    $html = self::$rewriteLogic->revSliderReplace($html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'cdn_rewrite_url') {
      return $html;
    }

    if (!empty($_GET['stop_debug']) && $_GET['stop_debug'] == 'cdn_rewrite') {
      return $html . print_r(array($_GET, $_POST), true);
    }


    $addslashes = false;
    if (!empty($_POST['action'])) {
      $addslashes = true;
    }

    // Find all URLs on page that have not been replaced
    $regEx = '#(?<=[(\"\'])(?:' . self::$regExURL . ')?/(?:((?:' . self::$regExDir . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';
    $html = preg_replace_callback($regEx, [$this, 'cdn_rewrite_url'], $html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'combine_css') {
      return $html;
    }

    //Combine CSS

    if ($criticalCombine || ($this->doCacheCombine() && (isset(self::$settings['css_combine']) && self::$settings['css_combine'] == '1'))) {
      if (empty($_GET['stopCombineCSS'])) {
        $combine_css = new wps_ic_combine_css();
        $html = $combine_css->maybe_do_combine($html);
      }
    }

    // Critical CSS Remove from Header
    if ((empty($_GET['disableCritical']) && empty($_GET['generateCriticalAPI'])) && !$this->criticalCombine) {
      if (!is_user_logged_in() && !is_admin_bar_showing()) {
        if (!empty(self::$settings['critical']['css']) && self::$settings['critical']['css'] == '1' && !self::$preloaderAPI) {
          global $post;
          $criticalCSS = new wps_criticalCss();
          $criticalCSSExists = $criticalCSS->criticalExists();

          if (!empty($_GET['forceCriticalAjax'])) {
            $html = self::$rewriteLogic->runCriticalAjax($html);
          } else {

            if (empty($criticalCSSExists)) {
              $criticalRunning = $criticalCSS->criticalRunning();
              if (!$criticalRunning) {
                set_transient('wpc_critical_ajax_' . md5($_SERVER['REQUEST_URI']), date('d.m.Y H:i:s'), 60 * 5);
                $html = self::$rewriteLogic->runCriticalAjax($html);
              }
            }

          }
        }

        if (!empty($_GET['debugCriticalRunning'])) {
          $html .= print_r(array(self::$settings['critical']['css'], $criticalCSSExists, $criticalRunning), true);
        }


        if (!empty($_GET['debugCritical_replace'])) {
          global $post;
          $criticalCSS = new wps_criticalCss();
          $criticalCSSExists = $criticalCSS->criticalExists();
          $criticalCSSContent = file_get_contents($criticalCSSExists['file']);
          return print_r(array($criticalCSSExists, $criticalCSSContent), true);
        }

        if (!empty($_GET['testCritical'])) {
          self::$settings['critical']['css'] = '1';
          $html = self::$rewriteLogic->lazyCSS($html);
          $html = self::$rewriteLogic->addCritical($html);
        }

        if (!empty(self::$settings['critical']['css']) && self::$settings['critical']['css'] == '1' && !self::$preloaderAPI) {
          if (!self::isURLExcluded('critical_css')) {
            global $post;
            $criticalCSS = new wps_criticalCss();
            $criticalCSSExists = $criticalCSS->criticalExists();

            if (!empty($criticalCSSExists)) {
              $html = self::$rewriteLogic->lazyCSS($html);
              $html = self::$rewriteLogic->addCritical($html);
            } else {
              //this way should be ok for multisite
            }
          }
        }
      }
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'externalUrls') {
      return $html;
    }

    if (self::$externalUrlEnabled == '1') {
      $html = self::$rewriteLogic->externalUrls($html);
    } else {
      if (!empty(self::$replaceAllLinks) && self::$replaceAllLinks == '1') {
        $html = self::$rewriteLogic->allLinks($html);
      }
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'fonts') {
      return $html;
    }

    if (self::$fonts == 1) {
      $html = self::$rewriteLogic->fonts($html);
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'decodeIframe') {
      return $html;
    }

    $html = self::$rewriteLogic->decodeIframe($html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'noscript_decode') {
      return $html;
    }

    $html = preg_replace_callback('/\[noscript\-wpc\](.*?)\[\/noscript\-wpc\]/i', [$this, 'noscript_decode'], $html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'Inline') {
      return $html;
    }

    //Inline css
    //or maybe just !is_admin?
    $inline_css = new wps_ic_inline_css();
    $html = $inline_css->doInline($html);

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'combine_js') {
      return $html;
    }

    //Combine JS
    if ($this->doCacheCombine() && (isset(self::$settings['js_combine']) && self::$settings['js_combine'] == '1')) {
      $combine_js = new wps_ic_combine_js();
      $html = $combine_js->maybe_do_combine($html);
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'delay_js') {
      return $html;
    }

    //Delay JS
    if (empty($_GET['disableDelay'])) {
      if (empty($_GET['disableCritical']) && !empty(self::$settings['delay-js']) && self::$settings['delay-js'] == '1' && !current_user_can('manage_options') && !self::$delay_js_override && !self::$preloaderAPI) {
        $js_delay = new wps_ic_js_delay();
        $html = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/si', [$js_delay, 'delay_script_replace'], $html);
        $html = preg_replace_callback('/<script\s+src="([^"]+)"[^>]*>/si', [$this, 'gtagDelay'], $html);
      }
    }

    if (empty($_GET['disableCritical']) && !empty(self::$settings['scripts-to-footer']) && self::$settings['scripts-to-footer'] == '1') {
      $js_delay = new wps_ic_js_delay();
      $html = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/si', [$js_delay, 'scriptsToFooter'], $html);
      $html = preg_replace_callback('/<\/body>/si', [$js_delay, 'printFooterScripts'], $html);
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'cache_minify') {
      return $html;
    }

    if (!empty(self::$settings['cache']['minify']) && self::$settings['cache']['minify'] == '1') {
      if (!self::isURLExcluded('minify_html')) {
        $html = self::$minifyHtml->minify($html);
      }
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'cache_settings') {
      return $html;
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'cache_settings') {
      return print_r(['settings' => self::$settings, 'advanced' => self::$settings['cache']['advanced'], 'html' => self::$settings['cache']['html'], 'mobile' => self::$settings['cache']['mobile'], 'is_mobile' => self::is_mobile(), 'url_excluded_simple' => self::isURLExcluded('simple_caching'), 'url_excluded_advanced' => self::isURLExcluded('cache')], true);
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'cache_advanced') {
      return $html;
    }

    if ((!empty(self::$settings['cache']['advanced']) && self::$settings['cache']['advanced'] == '1')) {
      if (!self::isExcludedFromCache($html) && $this->doCacheCombine()) {
        if (!self::is_mobile()) {
          return self::$cacheHtml->saveCache($html);
        } else {
          return self::$cacheHtml->saveCache($html, 'mobile');
        }
      }
    }

    if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'cache_mobile') {
      return $html;
    }


    return $html;
  }

  public function getRegexp()
  {
    if (!isset(self::$options['regExUrl']) || !isset(self::$options['regexpDirectories']) || empty(self::$options['regExUrl']) || empty(self::$options['regexpDirectories'])) {
      $escapedSiteURL = quotemeta(self::$home_url);
      self::$options['regExUrl'] = $regExURL = '(https?:|)' . substr($escapedSiteURL, strpos($escapedSiteURL, '//'));

      //Prep Included Directories
      $directories = 'wp\-content|wp\-includes';
      if (!empty($cdn['cdn_directories'])) {
        $directoriesArray = array_map('trim', explode(',', $cdn['cdn_directories']));

        if (count($directoriesArray) > 0) {
          $directories = implode('|', array_map('quotemeta', array_filter($directoriesArray)));
        }
      }

      self::$options['regexpDirectories'] = $directories;

      self::$regExURL = $regExURL;
      self::$regExDir = $directories;

      update_option(WPS_IC_OPTIONS, self::$options);
    } else {
      self::$regExURL = self::$options['regExUrl'];
      self::$regExDir = self::$options['regexpDirectories'];
    }
  }

  public static function isURLExcluded($setting)
  {
    if (!isset(self::$excludes[$setting]) || empty(self::$excludes[$setting])) return false;

    $url = self::$keys->url;
    $excludeList = self::$excludes[$setting];
    if (!empty($excludeList)) {
      foreach ($excludeList as $key => $value) {
        if ($value) {
          if (strpos($url, $value) !== false) {
            return true;
          }
        }
      }
    }

    return false;
  }

  public function gtagDelay($src)
  {
    // TODO: We have already delayed things, but speed tests don't recognize it
    $tag = $src[0];

    if (strpos($tag, 'wps-inline') !== false) {
      return $tag;
    }

    $srcToLower = strtolower($src[0]);

    if (strpos($srcToLower, 'googletag') !== false || strpos($srcToLower, 'gtag') !== false || strpos($srcToLower, 'facebook') !== false || strpos($srcToLower, 'recaptcha') !== false || strpos($srcToLower, 'tween') !== false || strpos($srcToLower, 'fontawesome') !== false) {
      if (strpos($srcToLower, 'type=') === false) {
        $tag = str_replace('<script', '<script type="wpc-delay-script"', $tag);
      } else {
        $tag = str_replace('type="text/javascript"', 'type="wpc-delay-script"', $tag);
      }

      return $tag;
    } elseif (preg_match('/ajax\.googleapis\.com\/ajax\/libs\/jquery\/\d+\.\d+\.\d+\/jquery\.min\.js/', $srcToLower, $matches)) {
      if (strpos($srcToLower, 'type=') === false) {
        $tag = str_replace('<script', '<script type="wpc-delay-script"', $tag);
      } else {
        $tag = str_replace('type="text/javascript"', 'type="wpc-delay-script"', $tag);
      }
      return $tag;
    }


    return $tag;
  }


  public function local_script_encode($html)
  {
    $found = strlen($html[0]);

    $encoded = base64_encode($html[0]);
    $decode = base64_decode($encoded);
    $replaced = strlen($decode);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'script') {
      return print_r([$html], true);
    }

    $slashed = addslashes($html[0]);
    $encoded = base64_encode($slashed);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_encode') {
      return print_r($encoded, true);
    }

    return '[script-wpc]' . $encoded . '[/script-wpc]';
  }

  public function local_script_decode($html)
  {
    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_decode') {
      return print_r([$html], true);
    }

    $decode = str_replace('[script-wpc]', '', $html[0]);
    $decode = str_replace('[/script-wpc]', '', $decode);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_decode_end') {
      return print_r([$decode], true);
    }

    $decode = base64_decode($decode);
    $decode = stripslashes($decode);

    return $decode;
  }

  public function crittr_replace_css($links)
  {
    preg_match_all('/([a-zA-Z\-\_]*)\s*\=["|\'](.*?)["|\']/is', $links[0], $linkAtts);

    if (!empty($_GET['dbg_links'])) {
      return print_r([$links], true);
    }

    if (!empty($_GET['dbg_links_atts'])) {
      return print_r([$linkAtts], true);
    }

    if (!empty($linkAtts[1])) {
      $linkHtml = '<link';
      $linkRel = '';

      $attNames = $linkAtts[1];
      $attValues = $linkAtts[2];

      foreach ($attNames as $i => $attName) {
        if ($attName == 'rel' && $attValues[$i] == 'dns-prefetch') {
          $linkRel = $attValues[$i];
        } elseif ($attName == 'href') {
          if (!empty($_GET['dbg_link_href'])) {
            return print_r([$attValues[$i], substr($attValues[$i], 0, 11)], true);
          }

          if (strpos($attValues[$i], self::$site_url) === false) {

          } else {

            if (strpos($attValues[$i], self::$zone_name) === false) {
              $attValues[$i] = WPS_IC_URI . 'fixCss.php?zoneName=' . self::$zone_name . '&css=' . urlencode($attValues[$i]) . '&rand=' . time();
            }

          }
        }

        $linkHtml .= ' ' . $attName . '="' . $attValues[$i] . '"';
      }

      if (!empty($_GET['dbg_links_output'])) {
        return print_r([$linkHtml], true);
      }

      $linkHtml .= '/>';

      if ($linkRel == 'stylesheet') {
        return $linkHtml;
      } else {
        return $links[0];
      }


    } else {
      return $links[0];
    }
  }

  public function replace_iframe_tags($iframe)
  {
    preg_match_all('/([a-zA-Z0-9\-\_]*)\s*\=["\']([^"]*)["\']?/is', $iframe[0], $iframeAtts);

    if (!empty($iframeAtts[1])) {
      $iFrame = '<iframe';
      $hasClass = false;

      $attNames = $iframeAtts[1];
      $attValues = $iframeAtts[2];

      if (!in_array('loading', $attNames)) {
        $attNames[] = 'loading';
      }

      foreach ($attNames as $i => $attName) {
        if ($attName == 'src') {
          $attName = 'data-src';
        } elseif ($attName == 'class') {
          $hasClass = true;
          $attValues[$i] .= ' wpc-iframe-delay';
        } elseif ($attName == 'loading') {
          $attValues[$i] = 'lazy';
        }

        $iFrame .= ' ' . $attName . '="' . $attValues[$i] . '" ';
      }

      if (!$hasClass) {
        $iFrame .= 'class="wpc-iframe-delay"';
      }

      $iFrame .= '></iframe>';

      return $iFrame;
    } else {
      return $iframe;
    }
  }

  public function maybe_addslashes($image, $addslashes = false)
  {
    if ($addslashes) {
      $image = addslashes($image);
    }

    return $image;
  }

  public function specialChars($url)
  {
    if (!self::$brizyActive) {
      $url = htmlspecialchars($url);
    }

    return $url;
  }


  public function cdn_rewrite_url($url, $addslashes = false)
  {
    $width = 1;

    if (self::$isAmp->isAmp()) {
      $width = 600;
    }

    $url = $url[0];
    if (strpos($url, 'cookie') !== false) {
      return $this->maybe_slash($url, $addslashes);
    }

    if (self::isExcluded('cdn', $url)) {
      return $this->maybe_slash($url, $addslashes);
    }

    $siteUrl = self::$home_url;

    $newUrl = str_replace($siteUrl, '', $url);

    // Check if site url is staging url? Anything after .com/something?
    preg_match('/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]\/([a-zA-Z0-9]+)/', $siteUrl, $isStaging);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'isstaging') {
      return print_r([$isStaging, $siteUrl], true);
    }

    // TODO: This is required for STAGING TO WORK!!! Don't remove SiteURL!!! LOOK for next TODO!!!

    $originalUrl = $url;
    $newSrcSet = '';

    preg_match_all('/((https?\:\/\/|\/\/)[^\s]+\S+\.(' . self::$findImages . '))\s(\d{1,5}+[wx])/', $url, $srcset_links);

    // TODO: Hrvoje fix for sites having bad srcset like https... 525w, https... without XYw
    if (!empty($srcset_links[0])) {
      if (!empty(self::$settings['remove-srcset'])) {
        return '';
      }
    }

    if (!empty($srcset_links[0])) {
      $debug = array();
      foreach ($srcset_links[0] as $i => $srcset) {
        $src = explode(' ', $srcset);
        $srcset_url = $src[0];
        $srcset_width = $src[1];


        if (self::is_excluded_link($srcset_url) || self::is_excluded($srcset_url, $srcset_url)) {
          $newSrcSet .= $srcset_url . ' ' . $srcset_width . ',';
        } else {
          if (strpos($srcset_width, 'x') !== false) {
            $width_url = 1;
            $srcset_width = str_replace('x', '', $srcset_width);
            $extension = 'x';
          } else {
            $width_url = $srcset_width = str_replace('w', '', $srcset_width);
            $extension = 'w';
          }

          if (strpos($srcset_url, self::$zone_name) !== false) {
            $newSrcSet .= $srcset_url . ' ' . $srcset_width . $extension . ',';
            continue;
          }

          $newSrcSet .= self::$apiUrl . '/r:' . self::$is_retina . '/wp:' . self::$webp . '/w:' . $width_url . '/u:' . self::reformat_url($srcset_url) . ' ' . $srcset_width . $extension . ',';
        }
      }

      $newSrcSet = rtrim($newSrcSet, ',');
      $newSrcSet = $this->maybe_slash($newSrcSet, $addslashes);
      return $newSrcSet;
    }
    else {
      if (strpos($url, 'data:image') !== false) {
        return $url;
      }

      if (self::is_excluded_link($url)) {
        return $this->maybe_slash($url, $addslashes);
      }

      if (!empty($_GET['dbg']) && $_GET['dbg'] == 'rewrite_url') {
        return print_r([$url], true);
      }

      if (strpos($url, self::$zone_name) !== false) {
        return $this->maybe_slash($url, $addslashes);
      }

      // External is disabled?
      if (empty(self::$externalUrlEnabled) || self::$externalUrlEnabled == '0') {
        if (!self::image_url_matching_site_url($url)) {
          return $this->maybe_slash($url, $addslashes);
        }
      } else {
        // Check if the URL is an image, then check if it's instagram etc...
        if (strpos($url, '.jpg') !== false || strpos($url, '.png') !== false || strpos($url, '.gif') !== false || strpos($url, '.svg') !== false || strpos($url, '.jpeg') !== false) {
          foreach (self::$default_excluded_list as $i => $excluded_string) {
            if (strpos($url, $excluded_string) !== false) {
              return $this->maybe_slash($url, $addslashes);
            }
          }
        }
      }

      if (!empty($url)) {

        // Todo: Quick fix for Password Protected Pages
        if (strpos($url, 'login') !== false) {
          return $this->maybe_slash($url, $addslashes);
        }

        if (strpos($url, '.css') !== false && self::$css == '1') {
          $fileMinify = self::$css_minify;

          if (self::isExcluded('css_minify', $url)) {
            $fileMinify = '0';
          }
          /**
           * CSS File
           */
          $newUrl = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($url);
        } elseif (strpos($url, '.js') !== false && self::$js == '1') {
          $fileMinify = self::$js_minify;
          if (self::isExcluded('js_minify', $url)) {
            $fileMinify = '0';
          }

          /**
           * JS File
           */
          if (strpos($url, 'wp-content') !== false || strpos($url, 'wp-includes') !== false) {
            if (empty(self::$js_minify) || self::$js_minify == 'false') {
              $newUrl = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($url, false);
            } else {
              $newUrl = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($url, false);
            }
          } else {
            $newUrl = 'https://' . self::$zone_name . '/m:' . $fileMinify . '/a:' . self::reformat_url($url, false);
          }
        } elseif (strpos($url, '.svg') !== false) {
          /**
           * JS File
           */
          if (!self::is_excluded($url, $url)) {
            if (self::$zone_test == 0 && (strpos($url, 'wp-content') !== false || strpos($url, 'wp-includes') !== false)) {
              $newUrl = 'https://' . self::$zone_name . '/m:0/a:' . self::reformat_url($url);
            } else {
              $newUrl = 'https://' . self::$zone_name . '/m:0/a:' . self::reformat_url($url, false);
            }
          }
        } elseif (self::$fonts == 1 && (strpos($url, '.woff') !== false || strpos($url, '.woff2') !== false || strpos($url, '.eot') !== false || strpos($url, '.ttf') !== false)) {
          /**
           * JS File
           */
          $newUrl = 'https://' . self::$zone_name . '/m:0/a:' . self::reformat_url($url);
        } else {
          /**
           * Something like an image?
           */

          if (self::is_image($url) && !self::is_excluded($url, $url)) {
            $newUrl = self::$apiUrl . '/r:' . self::$is_retina . '/wp:' . self::$webp . '/w:' . $width . '/u:' . self::reformat_url($url);
          }

          $newUrl = $this->maybe_slash($newUrl, $addslashes);

          return self::reformat_url($newUrl);
        }

        if (self::is_excluded($url, $url)) {
          return $this->maybe_slash($originalUrl, $addslashes);
        }
        if (!empty($_GET['dbg']) && $_GET['dbg'] == 'rewrite_url_to_file') {
          $fp = fopen(WPS_IC_DIR . 'rewrite_url_file.txt', 'a+');
          fwrite($fp, 'URL: ' . $url . "\r\n");
          fwrite($fp, 'URL: ' . $newUrl . "\r\n");
          fwrite($fp, '---' . "\r\n");
          fclose($fp);
        }

        // TODO: This is required for STAGING TO WORK!!! Don't remove SiteURL!!! LOOK for next TODO!!!
        if (self::$is_multisite) {
          return $this->maybe_slash($newUrl, $addslashes);
        } elseif (empty($isStaging) || empty($isStaging[0])) {
          // Not a staging site
          return $this->maybe_slash($newUrl, $addslashes);
        } else {
          // It's a staging site
          return $this->maybe_slash($originalUrl, $addslashes);
        }
      }

      return $this->maybe_slash($url, $addslashes);
    }
  }

  public function maybe_slash($url, $addslashes = false)
  {
    if ($addslashes) {
      return addslashes($url);
    }

    return $url;
  }

  public static function is_excluded($image_element, $image_link = '')
  {
    $image_path = '';

    if (empty($image_link)) {
      preg_match('@src="([^"]+)"@', $image_element, $match_url);
      if (!empty($match_url)) {
        $image_path = $match_url[1];
        $basename_original = basename($match_url[1]);
      } else {
        $basename_original = basename($image_element);
      }
    } else {
      $image_path = $image_link;
      $basename_original = basename($image_link);
    }

    preg_match("/([0-9]+)x([0-9]+)\.[a-zA-Z0-9]+/", $basename_original, $matches); //the filename suffix way
    if (empty($matches)) {
      // Full Image
      $basename = $basename_original;
    } else {
      // Some thumbnail
      $basename = str_replace('-' . $matches[1] . 'x' . $matches[2], '', $basename_original);
    }

    /**
     * Is this image lazy excluded?
     */
    if (!empty(self::$lazy_excluded_list) && !empty(self::$lazy_enabled) && self::$lazy_enabled == '1') {
      //return 'asd';
      foreach (self::$lazy_excluded_list as $i => $lazy_excluded) {
        if (strpos($basename, $lazy_excluded) !== false) {
          return true;
        }
      }
    } elseif (!empty(self::$excluded_list)) {
      foreach (self::$excluded_list as $i => $excluded) {
        if (strpos($basename, $excluded) !== false) {
          return true;
        }
      }
    }

    if (!empty(self::$lazy_excluded_list) && in_array($basename, self::$lazy_excluded_list)) {
      return true;
    }

    if (!empty(self::$excluded_list) && in_array($basename, self::$excluded_list)) {
      return true;
    }

    return false;
  }

  public static function is_image($image)
  {
    if (strpos($image, '.webp') === false && strpos($image, '.jpg') === false && strpos($image, '.jpeg') === false && strpos($image, '.png') === false && strpos($image, '.ico') === false && strpos($image, '.svg') === false && strpos($image, '.gif') === false) {
      return false;
    } else {
      // Serve JPG Enabled?
      if (strpos($image, '.jpg') !== false || strpos($image, '.jpeg') !== false) {
        // is JPEG enabled
        if (self::$settings['serve']['jpg'] == '0') {
          return false;
        }
      }

      // Serve GIF Enabled?
      if (strpos($image, '.gif') !== false) {
        // is JPEG enabled
        if (self::$settings['serve']['gif'] == '0') {
          return false;
        }
      }

      // Serve PNG Enabled?
      if (strpos($image, '.png') !== false) {
        // is PNG enabled
        if (self::$settings['serve']['png'] == '0') {
          return false;
        }
      }

      // Serve SVG Enabled?
      if (strpos($image, '.svg') !== false) {
        // is SVG enabled
        if (self::$settings['serve']['svg'] == '0') {
          return false;
        }
      }

      return true;
    }
  }


  public function local_image_tags($image)
  {
    $class_Addon = '';
    $image_tag = $image[0];
    $image_source = '';

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'local_start') {
      return print_r($image, true);
    }

    // File has already been replaced
    if ($this->defaultExcluded($image[0])) {
      return $image[0];
    }


    // File is not an image
    if (strpos($image[0], '.webp') === false && strpos($image[0], '.jpg') === false && strpos($image[0], '.jpeg') === false && strpos($image[0], '.png') === false && strpos($image[0], '.ico') === false && strpos($image[0], '.svg') === false && strpos($image[0], '.gif') === false) {
      return $image[0];
    }

    // File is excluded
    if (self::is_excluded($image[0])) {
      $image_source = $image[0];
      $image_source = preg_replace('/class=["|\'](.*?)["|\']/is', 'class="$1 wps-ic-loaded"', $image_source);

      return $image_source;
    }

    if ((self::$externalUrlEnabled == 'false' || self::$externalUrlEnabled == '0') && !self::image_url_matching_site_url($image[0])) {
      return $image[0];
    }

    // Original URL was
    $original_img_tag = array();
    $original_img_tag['original_tags'] = $this->getAllTags($image[0], []);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'searchImage') {
      return print_r([$image[0], $original_img_tag['original_tags']], true);
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'local_original_tags') {
      return print_r($original_img_tag['original_tags'], true);
    }

    if (!empty($original_img_tag['original_tags']['src']) && empty($original_img_tag['original_tags']['data-src'])) {
      $image_source = $original_img_tag['original_tags']['src'];
    } else {
      $image_source = $original_img_tag['original_tags']['data-src'];
    }

    $original_img_tag['original_src'] = $image_source;

    // Old Code Below

    // Figure out image class
    preg_match('/srcset=["|\']([^"]+)["|\']/', $image_tag, $image_srcset);
    if (!empty($image_srcset[1])) {
      $original_img_tag['srcset'] = $image_srcset[1];
    }

    $size = self::get_image_size($image_source);

    $svgAPI = $source_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="' . $size[0] . '" height="' . $size[1] . '"><path d="M2 2h' . $size[0] . 'v' . $size[1] . 'H2z" fill="#fff" opacity="0"/></svg>');

    // OriginalImageSource
    $original_img_src = $image_source;

    // Path to CSS File
    $site_url = str_replace(['https://', 'http://'], '', self::$site_url);
    $image_path = str_replace(['https://' . $site_url . '/', 'http://' . $site_url . '/'], '', $image_source);
    $image_path = explode('?', $image_path);
    $image_path = ABSPATH . $image_path[0];

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'local_settings') {
      $webP = str_replace(['.jpeg', '.jpg', '.png'], '.webp', $image_path);

      return print_r([self::$webp, $image_path, $webP, file_exists($webP)], true);
    }

    /**
     * Local File does not exists?
     */
    if (!file_exists($image_path)) {
      return $image[0];
    } else {
      if (self::$webp == 'true' || self::$webp == '1') {
        // Check if WebP Exists in PATH?
        $webP = str_replace(['.jpeg', '.jpg', '.png'], '.webp', $image_path);

        if (!file_exists($webP)) {
          $webP = false;
          $image_source = $original_img_src;
        } else {
          $original_img_src = str_replace(['.jpeg', '.jpg', '.png'], '.webp', $original_img_src);
          $image_source = $original_img_src;
        }
      } else {
        $image_source = $original_img_src;
      }
    }


    // Is LazyLoading enabled in the plugin?
    if (!empty(self::$lazy_enabled) && self::$lazy_enabled == '1' && !self::$lazy_override) {
      // If Logo remove wps-ic-lazy-image
      if (strpos($image_source, 'logo') !== false) {
        $image_tag = 'src="' . $image_source . '"';
      } else {
        $image_tag = 'src="' . $svgAPI . '"';
      }

      $image_tag .= ' loading="lazy"';
      $image_tag .= ' data-src="' . $image_source . '"';

      // If Logo remove wps-ic-lazy-image
      if (strpos($image_source, 'logo') !== false) {
        // Image is for logo
        $class_Addon .= 'wps-ic-local-lazy wps-ic-logo';
      } else {
        // Image is not for logo
        $class_Addon .= 'wps-ic-local-lazy wps-ic-lazy-image ';
      }
    } else if ((!empty(self::$native_lazy_enabled) && self::$native_lazy_enabled == '1' && !self::$lazy_override)) {
      $image_tag = 'src="' . $image_source . '"';
      $image_tag .= ' loading="lazy"';
    } else {
      if (!empty(self::$adaptive_enabled) && self::$adaptive_enabled == '1') {
        $image_tag = 'src="' . $image_source . '"';
        $image_tag .= ' data-adaptive="true"';
        $image_tag .= ' data-remove-src="true"';
      } else {
        $image_tag = 'src="' . $image_source . '"';
        $image_tag .= ' data-adaptive="false"';
      }

      $image_tag .= ' data-src="' . $image_source . '"';
    }

    /**
     * Srcset to WebP
     */
    $srcset_att = '';

    if (self::$webp == 'true' || self::$webp == '1') {
      if (!empty($original_img_tag['srcset'])) {
        $exploded_scrcset = explode(',', $original_img_tag['srcset']);
        if (!empty($exploded_scrcset)) {
          foreach ($exploded_scrcset as $i => $src) {
            $src = trim($src);
            $src_w = explode(' ', $src);

            if (!empty($src_w)) {
              $real_src = $src_w[0];
              $real_src_width = $src_w[1];

              $image_path = str_replace(self::$site_url . '/', '', $real_src);
              $image_path_webP = ABSPATH . $image_path;

              $webP = str_replace(['.jpeg', '.jpg', '.png'], '.webp', $real_src);
              $image_path_webP = str_replace(['.jpeg', '.jpg', '.png'], '.webp', $image_path_webP);

              if (!file_exists($image_path_webP)) {
                $srcset_att .= $real_src . ' ' . $real_src_width . ',';
              } else {
                $srcset_att .= $webP . ' ' . $real_src_width . ',';
              }
            }
          }
        }
        $srcset_att = rtrim($srcset_att, ',');
      }
    }


    if (empty($srcset_att)) {
      $srcset_att = $original_img_tag['srcset'];
    }

    $image_tag .= ' srcset="' . $srcset_att . '" ';

    if (!empty($original_img_tag['original_tags'])) {
      foreach ($original_img_tag['original_tags'] as $tag => $value) {
        if ($tag == 'class') {
          $value = $class_Addon . ' ' . $value;
        }

        if ($tag == 'src' || $tag == 'data-src') {
          continue;
        }

        $image_tag .= $tag . '="' . $value . '" ';
      }
    }

    return '<img ' . $image_tag . ' />';
  }

  public function getAllTags($image, $ignore_tags = ['src', 'srcset', 'data-src', 'data-srcset'])
  {
    $found_tags = array();

    preg_match_all('/([a-zA-Z\-\_]*)\s*\=["\']?((?:.(?!["\']?\s+(?:\S+)=|\s*\/?[>"\']))+.)["\']?/is', $image, $image_tags);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'getAllTags') {
      return print_r([$image_tags, true]);
    }

    if (!empty($image_tags[1])) {
      $tag_value = $image_tags[2];
      foreach ($image_tags[1] as $i => $tag) {
        if (!empty($ignore_tags) && in_array($tag, $ignore_tags)) {
          continue;
        }

        $tag_value[$i] = str_replace(['"', '\''], '', $tag_value[$i]);
        $found_tags[$tag] = $tag_value[$i];
      }
    }

    return $found_tags;
  }

  public static function get_image_size($url)
  {
    preg_match("/([0-9]+)x([0-9]+)\.[a-zA-Z0-9]+/", $url, $matches); //the filename suffix way
    if (isset($matches[1]) && isset($matches[2])) {
      return [$matches[1], $matches[2]];
      $sizes = [$matches[1], $matches[2]];
    } else { //the file
      return [1024, 1024];
    }

    return $sizes;
  }


}