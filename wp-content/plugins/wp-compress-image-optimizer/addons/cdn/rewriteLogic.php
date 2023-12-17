<?php

class wps_rewriteLogic
{

  public static $settings;
  public static $options;
  public static $siteUrl;
  public static $homeUrl;
  public static $zoneName;
  public static $randomHash;
  public static $siteUrlScheme;
  public static $excludedList;
  public static $lazyExcludeList;
  public static $defaultExcludedList;
  public static $externalUrlEnabled;
  public static $externalUrlExcluded;
  public static $emojiRemove;
  public static $preloaderAPI;
  public static $replaceAllLinks;

  // CSS / JS Variables
  public static $fonts;
  public static $css;
  public static $cssMinify;
  public static $cssImgUrl;
  public static $js;
  public static $jsMinify;

  // Integrations
  public static $perfMattersActive;
  public static $brizyActive;
  public static $brizyCache;
  public static $revSlider;

  // Lazy Tags
  public static $lazyLoadedImages;
  public static $lazyLoadedImagesLimit;
  public static $lazyLoadSkipFirstImages;
  public static $loadedImagesSt;
  public static $loadedImagesStLimit;
  public static $lazyOverride;
  public static $delayJsOverride;
  public static $deferJsOverride;
  public static $nativeLazyEnabled;

  // Api Params
  public static $apiUrl;
  public static $exif;
  public static $webp;
  public static $isRetina;
  public static $retinaEnabled;
  public static $adaptiveEnabled;
  public static $webpEnabled;
  public static $lazyEnabled;
  public static $removeSrcset;
  public static $isMobile;

  public static $removedCSS;
  public static $excludes;
  public static $excludes_class;
  public static $isAjax;
  public static $isAmp;

  public function __construct()
  {
    self::$settings = get_option(WPS_IC_SETTINGS);
    self::$options = get_option(WPS_IC_OPTIONS);
    self::$randomHash = 0;
    self::$preloaderAPI = 0;
    self::$isMobile = false;
    self::$isAmp = new wps_ic_amp();

    self::$settings = $this->runMissingSettings(self::$settings);

    self::$isAjax = (function_exists("wp_doing_ajax") && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX);

    if (!self::$isAjax && !empty($_POST)) {
      foreach ($_POST as $key => $value) {
        if (strpos($key, 'ajax') !== false) {
          self::$isAjax = true;
          break;
        }
      }
    }

    // Lazy Limits
    self::$lazyLoadedImages = 0;
    self::$lazyLoadedImagesLimit = 4;
    self::$lazyLoadSkipFirstImages = 4;

    self::$excludes_class = new wps_ic_excludes();
    self::$excludes = get_option('wpc-excludes');
	  self::$isAmp = new wps_ic_amp();

    /**
     * self::$isAjax was required for Ajax Filtering to work in Precommerce
     */
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'PreloaderAPI') !== false || !empty($_GET['dbg_preload'])) {
      self::$lazyLoadedImagesLimit = 9999;
      self::$preloaderAPI = 1;
      self::$lazyEnabled = 0;
      self::$nativeLazyEnabled = 0;
      self::$adaptiveEnabled = 0;
    }

    self::$loadedImagesSt = 0;
    self::$loadedImagesStLimit = 6;

    self::$nativeLazyEnabled = self::$settings['nativeLazy'];

    $this->setupSiteUrl();

    $this->setupExcludes();
    $this->setupApiParams();


    if ($this->isMobile()) {
      $this->setMobile();
    }

    $this->removeEmoji();
    $this->revSliderActive();
    $this->perfMatters();
    $this->Brizy();

    self::$externalUrlEnabled = 'false';

    // External URL Enabled?
    if (!empty(self::$settings['external-url'])) {
      self::$externalUrlEnabled = self::$settings['external-url'];
    }
  }

  public function runMissingSettings($settings)
  {
    $required = array('css', 'css_image_urls', 'css_minify', 'js', 'js_minify', 'emoji-remove', 'preserve_exit', 'fonts');
    foreach ($required as $key => $value) {
      if (empty($settings[$key]) || !isset($settings[$key])) {
        $settings[$key] = '';
      }
    }

    return $settings;
  }

  public function setupSiteUrl()
  {
    if (!is_multisite()) {
      self::$siteUrl = site_url();
      self::$homeUrl = home_url();
    } else {
      $current_blog_id = get_current_blog_id();
      switch_to_blog($current_blog_id);

      self::$siteUrl = network_site_url();
      self::$homeUrl = home_url();
    }

    $custom_cname = get_option('ic_custom_cname');
    if (empty($custom_cname) || !$custom_cname) {
      self::$zoneName = get_option('ic_cdn_zone_name');
    } else {
      self::$zoneName = $custom_cname;
    }

    self::$siteUrlScheme = parse_url(self::$siteUrl, PHP_URL_SCHEME);
  }

  public function setupExcludes()
  {
    self::$defaultExcludedList = ['redditstatic', 'ai-uncode', 'gtm', 'instagram.com', 'fbcdn.net', 'twitter', 'google', 'coinbase', 'cookie', 'schema', 'recaptcha', 'data:image', 'stats.jpg'];

    self::$lazyExcludeList = get_option('wpc-ic-lazy-exclude');
    self::$excludedList = get_option('wpc-ic-external-url-exclude');

    if (!is_array(self::$excludedList)) {
      self::$externalUrlExcluded = explode("\n", self::$excludedList);
    } else {
      self::$externalUrlExcluded = self::$excludedList;
    }
  }

  public function setupApiParams()
  {
    $conditions = array('css_image_urls', 'css_minify', 'js_minify', 'preserve_exif', 'emoji-remove', 'css', 'js');
    foreach ($conditions as $key => $condition) {
      if (is_array($condition)) {
        if (!isset(self::$settings[$condition[0]][$condition[1]])) {
          self::$settings[$condition[0]][$condition[1]] = '0';
        }
      } else {
        if (!isset(self::$settings[$condition])) {
          self::$settings[$condition] = '0';
        }
      }
    }

    self::$css = self::$settings['css'];
    self::$cssImgUrl = self::$settings['css_image_urls'];
    self::$cssMinify = self::$settings['css_minify'];
    self::$js = self::$settings['js'];
    self::$jsMinify = self::$settings['js_minify'];
    self::$emojiRemove = self::$settings['emoji-remove'];
    self::$exif = self::$settings['preserve_exif'];

    if (isset(self::$settings['fonts']) && !empty(self::$settings['fonts'])) {
      self::$fonts = self::$settings['fonts'];
    } else {
      self::$fonts = '0';
    }

    self::$isRetina = '0';
    self::$webp = '0';
    self::$externalUrlEnabled = 'false';

    if (empty(self::$settings['remove-srcset'])) {
      self::$settings['remove-srcset'] = '0';
    }

    self::$removeSrcset = self::$settings['remove-srcset'];
    self::$lazyEnabled = self::$settings['lazy'];
    self::$adaptiveEnabled = self::$settings['generate_adaptive'];
    self::$webpEnabled = self::$settings['generate_webp'];
    self::$retinaEnabled = self::$settings['retina'];
    if (!empty(self::$settings['replace-all-link'])) {
      self::$replaceAllLinks = self::$settings['replace-all-link'];
    } else {
      self::$replaceAllLinks = '0';
    }

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'PreloaderAPI') !== false || !empty($_GET['dbg_preload'])) {
      self::$lazyLoadedImagesLimit = 9999;
      self::$preloaderAPI = 1;
      self::$lazyEnabled = 0;
      self::$adaptiveEnabled = 0;
    }

    if (!empty($_GET['disableLazy'])) {
      self::$lazyEnabled = '0';
    }

    //
    if (!empty(self::$webpEnabled) && self::$webpEnabled == '1') {
      self::$webp = '1';
    } else {
      self::$webp = '0';
    }

    if (!empty(self::$retinaEnabled) && self::$retinaEnabled == '1') {
      if (isset($_COOKIE["ic_pixel_ratio"])) {
        if ($_COOKIE["ic_pixel_ratio"] >= 2) {
          self::$isRetina = '1';
        }
      }
    }

    // If Optimization Quality is Not set...
    if (empty(self::$settings['optimization']) || self::$settings['optimization'] == '' || self::$settings['optimization'] == '0') {
      self::$settings['optimization'] = 'i';
    }

    // Optimization Switch from Legacy
    switch (self::$settings['optimization']) {
      case 'intelligent':
        self::$settings['optimization'] = 'i';
        break;
      case 'ultra':
        self::$settings['optimization'] = 'u';
        break;
      case 'lossless':
        self::$settings['optimization'] = 'l';
        break;
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'direct') {
      if (!empty($_GET['custom_server'])) {
        self::$zoneName = $_GET['custom_server'] . '/key:' . self::$options['api_key'];
      }
    }

    if (!empty(self::$exif) && self::$exif == '1') {
      self::$apiUrl = 'https://' . self::$zoneName . '/q:' . self::$settings['optimization'] . '/e:1';
    } else {
      self::$apiUrl = 'https://' . self::$zoneName . '/q:' . self::$settings['optimization'] . '';
    }
  }


  public function isMobile()
  {
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
      if (preg_match('#^.*(2.0\ MMP|240x320|400X240|lighthouse|mobile|AvantGo|BlackBerry|Blazer|Cellphone|Danger|DoCoMo|Elaine/3.0|EudoraWeb|Googlebot-Mobile|hiptop|IEMobile|KYOCERA/WX310K|LG/U990|MIDP-2.|MMEF20|MOT-V|NetFront|Newt|Nintendo\ Wii|Nitro|Nokia|Opera\ Mini|Palm|PlayStation\ Portable|portalmmm|Proxinet|ProxiNet|SHARP-TQ-GX10|SHG-i900|Small|SonyEricsson|Symbian\ OS|SymbianOS|TS21i-10|UP.Browser|UP.Link|webOS|Windows\ CE|WinWAP|YahooSeeker/M1A1-R2D2|iPhone|iPod|Android|BlackBerry9530|LG-TU915\ Obigo|LGE\ VX|webOS|Nokia5800).*#i', $agent) || preg_match('#^(w3c\ |w3c-|acs-|alav|alca|amoi|audi|avan|benq|bird|blac|blaz|brew|cell|cldc|cmd-|dang|doco|eric|hipt|htc_|inno|ipaq|ipod|jigs|kddi|keji|leno|lg-c|lg-d|lg-g|lge-|lg/u|maui|maxo|midp|mits|mmef|mobi|mot-|moto|mwbp|nec-|newt|noki|palm|pana|pant|phil|play|port|prox|qwap|sage|sams|sany|sch-|sec-|send|seri|sgh-|shar|sie-|siem|smal|smar|sony|sph-|symb|t-mo|teli|tim-|tosh|tsm-|upg1|upsi|vk-v|voda|wap-|wapa|wapi|wapp|wapr|webc|winw|winw|xda\ |xda-).*#i', substr($agent, 0, 4))) {
        return true;
      }
    }

    return false;
  }

  public function setMobile()
  {
    self::$isMobile = true;
    self::$retinaEnabled = false;
    self::$isRetina = '0';
  }

  public function removeEmoji()
  {
    if (!empty(self::$emojiRemove) && self::$emojiRemove == '1') {
      remove_action('wp_head', 'print_emoji_detection_script', 7);
      remove_action('admin_print_scripts', 'print_emoji_detection_script');
      remove_action('wp_print_styles', 'print_emoji_styles');
      remove_action('admin_print_styles', 'print_emoji_styles');
      remove_filter('the_content_feed', 'wp_staticize_emoji');
      remove_filter('comment_text_rss', 'wp_staticize_emoji');
      remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    }
  }

  public function revSliderActive()
  {
    if (class_exists('RevSliderFront')) {
      self::$revSlider = true;
    }

    self::$revSlider = false;
  }

  public function perfMatters()
  {
    self::$perfMattersActive = false;

    //Perfmatters settings check
    if (function_exists('perfmatters_version_check')) {
      self::$perfMattersActive = self::isPerfMattersLazyActive();

      $perfmatters_options = get_option('perfmatters_options');

      if (!empty($perfmatters_options['assets']['delay_js']) && $perfmatters_options['assets']['delay_js']) {
        self::$delayJsOverride = 1;
      }

      if (!empty($perfmatters_options['assets']['defer_js']) && $perfmatters_options['assets']['defer_js']) {
        self::$deferJsOverride = 1;
      }

      if (!empty($perfmatters_options['lazyload']['lazy_loading']) && $perfmatters_options['lazyload']['lazy_loading']) {
        self::$lazyOverride = 1;
      }
    }
  }

  public static function isPerfMattersLazyActive()
  {
    if (defined('PERFMATTERS_ITEM_NAME')) {
      $options = get_option('perfmatters_options');
      if (!empty($options['lazyload']['lazy_loading'])) {
        return true;
      }
    }

    return false;
  }

  public function Brizy()
  {
    if (defined('BRIZY_VERSION')) {
      self::$brizyCache = get_option('wps_ic_brizy_cache');
      self::$brizyActive = true;
    } else {
      self::$brizyActive = false;
    }
  }


  public function revSliderReplace($html)
  {
    $html = preg_replace_callback('/data-thumb=[\'|"](.*?)[\'|"]/i', [__CLASS__, 'revSlider_Replace_DataThumb'], $html);

    return $html;
  }

  public function revSlider_Replace_DataThumb($image)
  {
    $image_url = $image[1];
    $webp = '/wp:' . self::$webp;
    if (self::isExcludedFrom('webp', $image_url)) {
      $webp = '';
    }

    if (self::isExcludedLink($image_url) || $this->defaultExcluded($image_url)) {
      return $image[0];
    } else {
      $NewSrc = 'https://' . self::$zoneName . '/q:' . self::$settings['optimization'] . '/r:' . self::$isRetina . $webp . '/w:480/u:' . $this->specialChars($image_url);

      return 'data-thumb="' . $NewSrc . '"';
    }

    return $image[0];
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

  public static function isExcludedLink($link)
  {
    /**
     * Is the link in excluded list?
     */
    if (empty($link)) {
      return false;
    }

    if (strpos($link, '.css') !== false || strpos($link, '.js') !== false) {
      foreach (self::$defaultExcludedList as $i => $excluded_string) {
        if (strpos($link, $excluded_string) !== false) {
          return true;
        }
      }
    }

    if (!empty(self::$excludedList)) {
      foreach (self::$excludedList as $i => $value) {
        if (strpos($link, $value) !== false) {
          // Link is excluded
          return true;
        }
      }
    }

    if (self::isExcludedFrom('cdn', $link)) {
      return true;
    }

    return false;
  }

  public function defaultExcluded($string)
  {
    foreach (self::$defaultExcludedList as $i => $excluded_string) {
      if (strpos($string, $excluded_string) !== false) {
        return true;
      }
    }

    return false;
  }

  public function specialChars($url)
  {
    if (!self::$brizyActive) {
      $url = htmlspecialchars($url);
    }

    return $url;
  }

  public function fonts($html)
  {
    $html = preg_replace_callback('/https?:[^)\'\'"]+\.(woff2|woff|eot|ttf)/i', [__CLASS__, 'replaceFonts'], $html);

    return $html;
  }

  public function replaceFonts($url)
  {
    $url = $url[0];

    if (strpos($url, self::$zoneName) === false) {
      if (strpos($url, '.woff') !== false || strpos($url, '.woff2') !== false || strpos($url, '.eot') !== false || strpos($url, '.ttf') !== false) {
        $newUrl = 'https://' . self::$zoneName . '/m:0/a:' . self::reformatUrl($url);

        return $newUrl;
      }
    }

    return $url;
  }

  public static function reformatUrl($url, $remove_site_url = false)
  {
    $url = trim($url);

    // Check if url is maybe a relative URL (no http or https)
    if (strpos($url, 'http') === false) {
      // Check if url is maybe absolute but without http/s
      if (strpos($url, '//') === 0) {
        // Just needs http/s
        $url = 'https:' . $url;
      } else {
        $url = str_replace('../wp-content', 'wp-content', $url);
        $url_replace = str_replace('/wp-content', 'wp-content', $url);
        $url = self::$siteUrl;
        $url = rtrim($url, '/');
        $url .= '/' . $url_replace;
      }
    }

    $formatted_url = $url;

    if (strpos($formatted_url, '?brizy_media') === false) {
      $formatted_url = explode('?', $formatted_url);
      $formatted_url = $formatted_url[0];
    }

    if ($remove_site_url) {
      $formatted_url = str_replace(self::$siteUrl, '', $formatted_url);
      $formatted_url = str_replace(str_replace(['https://', 'http://'], '', self::$siteUrl), '', $formatted_url);
      $formatted_url = str_replace(addcslashes(self::$siteUrl, '/'), '', $formatted_url);
      $formatted_url = ltrim($formatted_url, '\/');
      $formatted_url = ltrim($formatted_url, '/');
    }

    if (!empty(self::$cdnEnabled) && self::$cdnEnabled == '1') {
      if (self::$randomHash == 0 && (strpos($formatted_url, '.css') !== false)) {
        $formatted_url .= '?icv=' . WPS_IC_HASH;
      }

      if (self::$randomHash == 0 && strpos($formatted_url, '.js') !== false) {
        $formatted_url .= '?js_icv=' . WPS_IC_JS_HASH;
      }
    }

    return $formatted_url;
  }

  public function allLinks($html)
  {
    $html = preg_replace_callback('/https?:(\/\/[^"\']*\.(?:svg|css|js|ico|icon))/i', [__CLASS__, 'cdnAllLinks'], $html);

    return $html;
  }

  public function cdnAllLinks($image)
  {
    $src_url = $image[0];

    if ($this->defaultExcluded($src_url)) {
      return $src_url;
    }

    if (self::isExcludedFrom('cdn', $src_url)) {
      return $src_url;
    }

    if (strpos($src_url, self::$zoneName) !== false) {
      return $src_url;
    }

    if (!self::isExcludedLink($src_url)) {
      // External is disabled?
      if (self::$externalUrlEnabled == '0' || empty(self::$externalUrlEnabled)) {
        if (!self::imageUrlMatchingSiteUrl($src_url)) {
          return $src_url;
        }
      }

      if (strpos($src_url, self::$zoneName) === false) {
        if (strpos($src_url, '.css') !== false) {
          if (self::$css == "1") {
            $fileMinify = self::$cssMinify;
            if (self::isExcluded('css_minify', $src_url)) {
              $fileMinify = '0';
            }

            $newSrc = 'https://' . self::$zoneName . '/m:' . $fileMinify . '/a:' . self::reformatUrl($src_url);
          }
        } elseif (strpos($src_url, '.js') !== false) {
          if (self::$js == "1") {
            $fileMinify = self::$jsMinify;
            if (self::isExcluded('js_minify', $src_url)) {
              $fileMinify = '0';
            }

            $newSrc = 'https://' . self::$zoneName . '/m:' . $fileMinify . '/a:' . self::reformatUrl($src_url);
          }
        } else {
          $newSrc = 'https://' . self::$zoneName . '/m:0/a:' . self::reformatUrl($src_url);
        }

        return $newSrc;
      }
    }

    return $image[0];
  }

  /**
   * Is link matching the site url?
   *
   * @param $image
   *
   * @return bool
   */
  public static function imageUrlMatchingSiteUrl($image)
  {
    $site_url = self::$siteUrl;
    $image = str_replace(['https://', 'http://'], '', $image);
    $site_url = str_replace(['https://', 'http://'], '', $site_url);

    if (strpos($image, '.css') !== false || strpos($image, '.js') !== false) {
      foreach (self::$defaultExcludedList as $i => $excluded_string) {
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

  public static function isExcluded($image_element, $image_link = '')
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
    if (!empty(self::$lazyExcludeList) && !empty(self::$lazyEnabled) && self::$lazyEnabled == '1') {
      //return 'asd';
      foreach (self::$lazyExcludeList as $i => $lazy_excluded) {
        if (strpos($basename, $lazy_excluded) !== false) {
          return true;
        }
      }
    } elseif (!empty(self::$excludedList)) {
      foreach (self::$excludedList as $i => $excluded) {
        if (strpos($basename, $excluded) !== false) {
          return true;
        }
      }
    }

    if (!empty(self::$lazyExcludeList) && in_array($basename, self::$lazyExcludeList)) {
      return true;
    }

    if (!empty(self::$excludedList) && in_array($basename, self::$excludedList)) {
      return true;
    }

    return false;
  }

  public function externalUrls($html)
  {
    $html = preg_replace_callback('/https?:[^)\s]+\.(jpg|jpeg|png|gif|svg|css|js|ico|icon)(?![^.\w]*\.[^.\w]*)/i', [__CLASS__, 'cdnExternalUrls'], $html);

    return $html;
  }

  public function cdnExternalUrls($image)
  {
    $src_url = $image[0];
    $width = 1;

    if (self::$isAmp->isAmp()) {
      $width = 600;
    }

    if (self::isExcludedFrom('cdn', $src_url) || $src_url == 'https://www.ico') {
      return $src_url;
    }

    // Is URL Matching the Site Url?
    if (strpos($src_url, self::$zoneName) !== false) {
      return $src_url;
    }

    $webp = '/wp:' . self::$webp;
    if (self::isExcludedFrom('webp', $src_url)) {
      $webp = '';
    }

    if (self::isExcludedFrom('cdn', $src_url)) {
      return $src_url;
    }

    if (!self::isExcludedLink($src_url)) {
      if (strpos($src_url, self::$zoneName) === false) {
        // Check if the URL is an image, then check if it's instagram etc...
        foreach (self::$defaultExcludedList as $i => $excluded_string) {
          if (strpos($src_url, $excluded_string) !== false) {
            return $src_url;
          }
        }

        $newSrc = $src_url;
        if (strpos($src_url, '.css') !== false) {
          if (self::$css == "1") {
            $newSrc = 'https://' . self::$zoneName . '/m:' . self::$cssMinify . '/a:' . self::reformatUrl($src_url);
          }
        } elseif (strpos($src_url, '.js') !== false) {
          if (self::$js == "1") {
            $newSrc = 'https://' . self::$zoneName . '/m:' . self::$jsMinify . '/a:' . self::reformatUrl($src_url);
          }
        } else {
          if (strpos($src_url, '.svg') !== false) {
            $newSrc = 'https://' . self::$zoneName . '/m:0/a:' . self::reformatUrl($src_url);
          } else {
            $newSrc = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth($width) . '/u:' . self::reformatUrl($src_url);
          }
        }
        return $newSrc;
      }
    }

    return $image[0];
  }

  public static function getCurrentMaxWidth($Width)
  {
    if (self::$isMobile) {
      return 320;
    }

    if ($Width == 'logo') {
      return '1';
    }

    return $Width;
  }

  public function favIcon($html)
  {
    $html = preg_replace_callback('/<link\s+([^>]+[\s\'"])?rel\s*=\s*[\'"]icon[\'"]/is', [__CLASS__, 'checkFavIcon'], $html);

    return $html;
  }

  public function checkFavIcon($html)
  {
    if (empty($html)) {
      return 'no favicon';
    } else {
      return print_r(array($html), true);
    }
  }

  public function runCriticalAjax($html)
  {
    $html = preg_replace_callback('/<\/body>/si', [__CLASS__, 'addCriticalAjax'], $html);
    return $html;
  }

  public function addCriticalAjax($args)
  {
    global $post;

    if (!empty($_GET['test_adding_critical_ajax'])) {
      $script = print_r($post, true);
      $script .= print_r($realUrl = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true);
      return $script;
    }

    $script = '';
    if (isset($post) && !empty($post->ID)) {

      $realUrl = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

      // TODO: Issues if DelayJS is disabled
      $script .= '<script type="text/javascript">';
      $script .= 'let wpcRunningCritical=false;';
      $script .= 'jQuery(document).on("keydown mousedown mousemove touchmove touchstart touchend wheel visibilitychange load", function(){';
      $script .= 'if (wpcRunningCritical) { return; }';
      $script .= 'wpcRunningCritical=true;';
      $script .= 'jQuery.post(ngf298gh738qwbdh0s87v_vars.ajaxurl, {action: "wpc_send_critical_remote", postID:"' . $post->ID . '", realUrl:"' . $realUrl . '"}, function (response) {
                if (response.success) {
                    console.log("Started Critical Call");
                }';
      $script .= '});';
      $script .= 'jQuery(document).off("keydown mousedown mousemove touchmove touchstart touchend wheel visibilitychange load");';
      $script .= '});';
      $script .= '</script>';
    }
    return $script . '</body>';
  }

  public function addCritical($html)
  {
    $html = preg_replace_callback('/<head>/si', [__CLASS__, 'addCriticalCSS'], $html);
    return $html;
  }

  public function addCriticalCSS($html)
  {
    $output = '<head>';
    $criticalCSS = new wps_criticalCss();
    $criticalCSSExists = $criticalCSS->criticalExists();
    if (!empty($criticalCSSExists)) {
      $criticalCSSContent_Desktop = $criticalCSSExists['desktop'];
      $criticalCSSContent_Mobile = $criticalCSSExists['mobile'];

      if ($this->isMobile()) {
        if (!empty($criticalCSSContent_Mobile)) {
          $output .= "\r\n" . '<style type="text/css" id="wpc-critical-css wpc-critical-css-mobile">' . file_get_contents($criticalCSSContent_Mobile) . '</style>';
        }
      } else {
        if (!empty($criticalCSSContent_Desktop)) {
          $output .= "\r\n" . '<style type="text/css" id="wpc-critical-css wpc-critical-css-desktop">' . file_get_contents($criticalCSSContent_Desktop) . '</style>';
        }
      }


    }

    return $output;
  }


  public function optimizeGoogleFonts($html)
  {
    $pattern = '/<link\s+[^>]*href=["\']([^"\']*fonts\.googleapis\.com\/css[^"\']*)["\'][^>]*>/i';
    $html = preg_replace_callback($pattern, [__CLASS__, 'optimizeGoogleFontsRewrite'], $html);
    return $html;
  }


  public function optimizeGoogleFontsRewrite($html)
  {
    $html = '';
    return $html;
  }


  public function lazyCSS($html)
  {
    $html = preg_replace_callback('/<link(.*?)>/si', [__CLASS__, 'cssLinkLazy'], $html);
    return $html;
  }

  public function cssLinkLazy($html)
  {

    $fullTag = $html[0];

    $criticalCSS = new wps_criticalCss();
    $criticalCSSExists = $criticalCSS->criticalExists();
    if (empty($criticalCSSExists)) {
      return $fullTag;
    }

    // Not Mobile
    $lazyCss = 'wpc-stylesheet';

    if (strpos($fullTag, 'rs6') !== false) {
      return $fullTag;
    }


    if (strpos($fullTag, 'elementor-post') !== false || strpos($fullTag, '/elementor/') !== false || strpos($fullTag, 'admin-bar') !== false) {
      $lazyCss = 'wpc-mobile-stylesheet';
    } elseif (strpos($fullTag, 'preload') !== false) {
      $lazyCss = 'wpc-mobile-stylesheet';
    }

    if (self::$excludes_class->strInArray($fullTag, self::$excludes_class->criticalCSSExcludes())) {
      return $fullTag;
    }

    preg_match('/(href)\s*\=["\']?((?:.(?!["\']?\s+(?:\S+)=|\s*\/?[>"\']))+.)["\']?/is', $fullTag, $href);
    if (!empty($href[2])) {

      if (strpos($href[2], 'fonts.googleapis.com/css') !== false) {
        // Google Fonts Hack?
        if (strpos($href[2], 'display=swap') === false) {
          $newHref = $href[2] . '&display=swap';
        } else {
          $newHref = $href[2];
        }
        $gfonts = '<link rel="preload" href="' . $newHref . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"/>';
        return $gfonts;
      } elseif (strpos($href[2], self::$siteUrl) === false || strpos($href[2], 'dynamic') !== false || strpos($href[2], 'layout') !== false) {
        return $fullTag;
      } else {
        $lazyCss = 'wpc-mobile-stylesheet';
      }
    }

    preg_match('/(rel)\s*\=["\']?((?:.(?!["\']?\s+(?:\S+)=|\s*\/?[>"\']))+.)["\']?/is', $fullTag, $linkRel);
    if (!empty($linkRel)) {
      if (!empty($linkRel[2])) {
        $relTag = $linkRel[0]; // rel="stylesheet"
        $relKey = $linkRel[1]; // rel
        $relValue = $linkRel[2]; // stylesheet

        if ($relValue == 'stylesheet') {
          $newTag = str_replace($relValue, $lazyCss, $relTag);
          $fullTag = str_replace($relTag, $newTag, $fullTag);
        }
      }
    }

    preg_match('/(type)\s*\=["\']?((?:.(?!["\']?\s+(?:\S+)=|\s*\/?[>"\']))+.)["\']?/is', $fullTag, $linkType);
    if (!empty($linkType)) {
      if (!empty($linkType[2])) {
        $relTag = $linkType[0]; // type="text/css"
        $relKey = $linkType[1]; // type
        $relValue = $linkType[2]; // text/css

        if ($relValue == 'text/css') {
          $newTag = str_replace($relValue, 'wpc-text/css', $relTag);
          $fullTag = str_replace($relTag, $newTag, $fullTag);
        }
      }
    }

    return $fullTag;
    #}
  }

  public function cssToFooter($html)
  {
    $html = preg_replace_callback('/<\/body>/si', [__CLASS__, 'cssToFooterRender'], $html);

    return $html;
  }

  public function cssToFooterRender($html)
  {
    return self::$removedCSS . '</body>';
  }

  public function encodeIframe($html)
  {
    $html = preg_replace_callback('/<iframe.*?\/iframe>/i', [__CLASS__, 'iframeEncode'], $html);

    return $html;
  }

  public function decodeIframe($html)
  {
    $html = preg_replace_callback('/\[iframe\-wpc\](.*?)\[\/iframe\-wpc\]/i', [__CLASS__, 'iframeDecode'], $html);

    return $html;
  }

  public function iframeEncode($html)
  {
    $html = base64_encode($html[0]);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_encode') {
      return print_r([$html], true);
    }

    return '[iframe-wpc]' . $html . '[/iframe-wpc]';
  }

  public function iframeDecode($html)
  {
    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'bas64_decode') {
      return print_r([$html], true);
    }

    $html = base64_decode($html[1]);

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'after_base64_decode') {
      return $html;
    }

    return $html;
  }

  public function scriptContent($html)
  {
    $html = preg_replace_callback('/<script\s[^>]*(?<=type=\"text\/template\")*>.*?<\/script>/is', [__CLASS__, 'scriptContentTag'], $html);

    return $html;
  }

  public function scriptContentTag($html)
  {
    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'script') {
      return print_r([$html], true);
    }

    if (strpos($html[0], 'text/template') !== false && strpos($html[0], 'text/x-template') !== false) {
      return $html[0];
    }

    $html = preg_replace_callback('/<img[^>]*>/si', [__CLASS__, 'imageTagAsset'], $html[0]);

    return $html;
  }

  public function imageTagAsset($image)
  {

    $image[0] = trim($image[0]);
    $addslashes = false;

    if (strpos($image[0], '$') !== false) {
      return $image[0];
    }

    if (!empty($_GET['dbg']) && $_GET['dbg'] == 'image_asset_array') {
      return print_r([str_replace('<img', 'sad', $image[0])], true);
    }

    if (strpos($image[0], '=\"') !== false || strpos($image[0], "=\'") !== false) {
      $addslashes = true;
      $image[0] = stripslashes($image[0]);
    }

    if (strpos($_SERVER['REQUEST_URI'], 'embed') !== false) {
      $image[0] = $this->maybe_addslashes($image[0], $addslashes);

      return $image[0];
    }

    // File has already been replaced
    if ($this->defaultExcluded($image[0])) {
      $image[0] = $this->maybe_addslashes($image[0], $addslashes);

      return $image[0];
    }

    // File is not an image
    if (!self::isImage($image[0])) {
      $image[0] = $this->maybe_addslashes($image[0], $addslashes);

      return $image[0];
    }

    if ((self::$externalUrlEnabled == 'false' || self::$externalUrlEnabled == '0') && !self::imageUrlMatchingSiteUrl($image[0])) {
      $image[0] = $this->maybe_addslashes($image[0], $addslashes);

      return $image[0];
    }

    // File is excluded
    if (self::isExcluded($image[0])) {
      $image[0] = $this->maybe_addslashes($image[0], $addslashes);

      return $image[0];
    }

    $img_tag = $image[0];
    $original_img_tag['original_tags'] = $this->getAllTags($image[0], []);

    preg_match('/src=["|\']([^"]+)["|\']/', $img_tag, $image_src);

    if (strpos($image_src[1], '$') !== false) {
      $image[0] = $this->maybe_addslashes($image[0], $addslashes);

      return $image[0];
    }

    if (!empty($image_src[1])) {
      $NewSrc = 'https://' . self::$zoneName . '/m:0/a:' . $this->specialChars($image_src[1]);
      $img_tag = str_replace($image_src[1], $NewSrc, $img_tag);
    }

    // TODO: Was required for some sites that were having slashes
    $img_tag = $this->maybe_addslashes($img_tag, true);

    return $img_tag;
  }

  public function maybe_addslashes($image, $addslashes = false)
  {
    if ($addslashes) {
      $image = addslashes($image);
    }

    return $image;
  }

  public static function isImage($image)
  {
    if (strpos($image, '.webp') === false && strpos($image, '.jpg') === false && strpos($image, '.jpeg') === false && strpos($image, '.png') === false && strpos($image, '.ico') === false && strpos($image, '.svg') === false && strpos($image, '.gif') === false) {
      return false;
    } else {
      // Serve JPG Enabled?
      if (strpos($image, '.jpg') !== false || strpos($image, '.jpeg') !== false) {
        // is JPEG enabled
        if (empty(self::$settings['serve']['jpg']) || self::$settings['serve']['jpg'] == '0') {
          return false;
        }
      }

      // Serve GIF Enabled?
      if (strpos($image, '.gif') !== false) {
        // is JPEG enabled
        if (empty(self::$settings['serve']['gif']) || self::$settings['serve']['gif'] == '0') {
          return false;
        }
      }

      // Serve PNG Enabled?
      if (strpos($image, '.png') !== false) {
        // is PNG enabled
        if (empty(self::$settings['serve']['png']) || self::$settings['serve']['png'] == '0') {
          return false;
        }
      }

      // Serve SVG Enabled?
      if (strpos($image, '.svg') !== false) {
        // is SVG enabled
        if (empty(self::$settings['serve']['svg']) || self::$settings['serve']['svg'] == '0') {
          return false;
        }
      }

      return true;
    }
  }

  public function getAllTags($image, $ignore_tags = ['src', 'srcset', 'data-src', 'data-srcset'])
  {
    $found_tags = array();

    if (strpos($image, 'trp-gettext') !== false) {
      //TRP inserts <trp-gettext data-trpgettextoriginal=19> ... </trp-gettext> to translate alt tag, breaks our usuall regex
      preg_match_all('/\s*([a-zA-Z-:]+)\s*=\s*("|\')(.*?)\2/is', $image, $image_tags);

      if (!empty($image_tags[1])) {
        $image_tags[2] = $image_tags[3];
      }

    } else {
      $image = html_entity_decode($image);
      preg_match_all('/([a-zA-Z\-\_]*)\s*\=["\']?((?:.(?!["\']?\s+(?:\S+)=|\s*\/?[>"\']))+.)["\']?/is', $image, $image_tags);
    }

    if (!empty($_GET['dbg_img'])) {
      return array($image, $image_tags);
    }

    if (!empty($image_tags[1])) {
      $tag_value = $image_tags[2];
      foreach ($image_tags[1] as $i => $tag) {
        if (!empty($ignore_tags) && in_array($tag, $ignore_tags)) {
          continue;
        }

        if ($tag == 'data-mk-image-src-set') {
          $value = htmlspecialchars_decode($tag_value[$i]);
          $value = json_decode($value, true);
          $value = $value['default'];
          $tag_value[$i] = $value;
        } else {
          $tag_value[$i] = str_replace(['"', '\''], '', $tag_value[$i]);
        }

        $found_tags[$tag] = $tag_value[$i];
      }
    }

    return $found_tags;
  }

  public function getPictureTags($image, $ignore_tags)
  {
    $extractedTags = [];
    $found_tags = [];
    $image = html_entity_decode($image);

    // Find all source tags
    preg_match_all('/<source[^>]*srcset="([^"]+)"/is', $image, $image_tags);

    // Gets All Tags - works
    #preg_match_all('/\s*([a-zA-Z-:]+)\s*=\s*("|\')(.*?)\2/is', $image, $image_tags);

    if (!empty($_GET['dbgExtract'])) {
      return array($image, $image_tags);
    }

    if (!empty($image_tags)) {
      $attributes = $image_tags[1];
      $values = $image_tags[3];

      if (!empty($attributes)) {
        foreach ($attributes as $index => $name) {
          $value = $values[$index];
          $extractedTags[$name] = $value;
        }
      }

      return $extractedTags;
    }

    return false;
  }

  public function defferFontAwesome($html)
  {
    // TODO: Fix causes problems with Crsip on WP Compress Site

    if (preg_match("/<script\b[^>]*\bsrc=['\"]([^'\"]*kit\.fontawesome[^'\"]*)['\"][^>]*>.*?<\/script>/si", $html, $matches)) {
      $scriptTag = $matches[0];

      if (!empty($_GET['stop_before']) && $_GET['stop_before'] == 'defferFontAwesome') {
        return print_r(array($matches), true);
      }

      if (strpos($scriptTag, 'defer') === false) {
        $scriptTag = str_replace('<script', '<script defer', $scriptTag);
      }

      $replace = str_replace($matches[0], $scriptTag, $html);
      return $replace;
    }

    return $html;
  }


  public function defferGtag($html)
  {
    // TODO: Fix causes problems with Crsip on WP Compress Site
    $pattern = '/<script\b[^>]*src\s*=\s*"[^"]*gtag[^"]*"[^>]*><\/script>/si';

    if (preg_match($pattern, $html, $matches)) {
      $scriptTag = $matches[0];
      if (strpos(strtolower($scriptTag), 'type=') === false) {
        $scriptTag = str_replace('<script', '<script type="wpc-delay-script"', $scriptTag);
      } else {
        $scriptTag = str_replace('text/javascript', 'wpc-delay-script', $scriptTag);
      }
      $replace = str_replace($matches[0], $scriptTag, $html);
      return $replace;
    }
    return $html;
  }


  public function defferAssets($html)
  {
    // TODO: Fix causes problems with Crsip on WP Compress Site
    return $html;
  }

  public function backgroundSizing($html)
  {
    $html = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>?/is', [__CLASS__, 'replaceBackgroundImagesInCSS'], $html);
    $html = preg_replace_callback('/data-settings=(["\'])(.*?)\1/i', [__CLASS__, 'replaceBackgroundDataSetting'], $html);
    return $html;
  }

  public function replaceBackgroundImagesInCSS($image)
  {
    $style_content = $image[0];

    $html = preg_replace_callback('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', [__CLASS__, 'replaceBackgroundImageStyles'], $style_content);

    return $html;
  }

  public function replaceBackgroundImage($image)
  {
    $tag = $image[0];
    $url = $image['image'];
    $original_url = $url;

    if (strpos($url, self::$zoneName) == false) {
      // File has already been replaced
      if ($this->defaultExcluded($url)) {
        return $tag;
      }

      // File is not an image
      if (!self::isImage($url)) {
        return $tag;
      }
    }

    if (self::isExcluded($url)) {
      return $tag;
    }

    if (self::isExcludedFrom('cdn', $url)) {
      return $tag;
    }

    $webp = '/wp:' . self::$webp;
    if (self::isExcludedFrom('webp', $url)) {
      $webp = '';
    }

    $newUrl = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($url);
    $return_tag = str_replace($original_url, $newUrl, $tag);

    if (self::$lazy_enabled) {
      $return_tag .= 'display:none;';
    }

    if (!empty($_GET['dbgBgRep'])) {
      return print_r(array($newUrl, self::$apiUrl), true);
    }

    return $return_tag;
  }


  public function replaceBackgroundDataSetting($image)
  {
    $data = html_entity_decode($image[2]);
    $dataJson = json_decode($data);

    $slides = $dataJson->background_slideshow_gallery;

    if (!empty($slides)) {
      foreach ($slides as $i => $slide) {
        $newSlideUrl = 'https://' . self::$zoneName . '/m:0/a:' . self::reformatUrl($slide->url);
        $dataJson->background_slideshow_gallery[$i]->url = $newSlideUrl;
      }

      $dataJsonNew = json_encode($dataJson);
      $dataJsonHTML = htmlentities($dataJsonNew);

      return ' data-settings="' . $dataJsonHTML . '" ';
    }

    if (strpos($image[2], '"') !== false) {
      return " data-settings='" . $image[2] . "' ";
    }

    return ' data-settings="' . $image[2] . '" ';
  }


  public function replaceBackgroundImageStyles($image)
  {
    $tag = $image[0];
    $url = $image['image'];
    $original_url = $url;

    if (strpos($url, self::$zoneName) == false) {
      // File has already been replaced
      if ($this->defaultExcluded($url)) {
        return $tag;
      }

      // File is not an image
      if (!self::isImage($url)) {
        return $tag;
      }

      if (self::isExcluded($url)) {
        return $tag;
      }

      if (self::isExcludedFrom('cdn', $url)) {
        return $tag;
      }

      $webp = '/wp:' . self::$webp;
      if (self::isExcludedFrom('webp', $url)) {
        $webp = '';
      }

      $newUrl = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($url);
      $return_tag = str_replace($original_url, $newUrl, $tag);

      if (!empty($return_tag)) {
        return $return_tag;
      } else {
        return $tag;
      }
    } else {
      return $tag;
    }
  }

  public function replacePictureTags($html)
  {
    $html = preg_replace_callback('/<picture\b[^>]*>(.*?)<\/picture>/is', [__CLASS__, 'replaceSourceTags'], $html);
    return $html;
  }


  public function replaceImageTags($html)
  {
    $html = preg_replace_callback('/(?<![\"|\'])<img[^>]*>/i', [__CLASS__, 'replaceImageTagsDo'], $html);

    return $html;
  }

  public function replaceImageTagsDoSlash($image)
  {
    if (strpos($_SERVER['REQUEST_URI'], 'embed') !== false) {
      return $image[0];
    }

    if (!empty($_GET['dbgAjax'])) {
      return print_r(array($_SERVER, wp_doing_ajax(), self::$isAjax, $image[0]), true);
    }

    if ($this->checkIsSlashed($image[0])) {
      $imageElement = stripslashes($image[0]);
    }

    $newImageElement = '';
    $original_img_tag = array();
    $original_img_tag['original_tags'] = $this->getAllTags($imageElement, []);

    if (!empty($_GET['ajaxImage'])) {
      return print_r(array($original_img_tag, $imageElement), true);
    }

    if (strpos($original_img_tag['original_tags']['src'], 'data:image') !== false || strpos($original_img_tag['original_tags']['src'], 'blank') !== false) {
      $newImageElement = $imageElement;
    } else {
      $newImageElement = '<img ';
      // it's placeholder or blank file change something
      foreach ($original_img_tag['original_tags'] as $tag => $value) {
        if ($tag == 'data-src' || $tag == 'src') {
          $src = $value;

          $webp = '/wp:' . self::$webp;
          if (self::isExcludedFrom('webp', $src)) {
            $webp = '/wp:0';
          }

          $src = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($src);
          $newImageElement .= 'src="' . $src . '" ';
        } else {
          $newImageElement .= $tag . '="' . $value . '" ';
        }
      }
      $newImageElement .= '/>';
    }

    if ($this->checkIsSlashed($image[0])) {
      $newImageElement = addslashes($newImageElement);
    }

    return $newImageElement;
  }

  public function checkIsSlashed($string)
  {
    $slashed = addslashes($string);

    if ($string !== stripslashes($slashed)) {
      return true;
    } else {
      return false;
    }
  }

  public function replaceSourceTags($html)
  {
    // Get just the inside of <picture> tag
    $insideElements = $html[1];
    $html = preg_replace_callback('/(?:https?:\/\/|\/)[^\s]+\.(jpg|jpeg|png|gif|svg|webp)/i', [__CLASS__, 'replaceSourceSrcset'], $html);
    return $html[0];
  }

  public function replaceSourceSrcset($html)
  {
    $url = $html[0];

    if (empty($url)) return $html[0];

    if (strpos($url, self::$zoneName) == false) {
      // File has already been replaced
      if ($this->defaultExcluded($url)) {
        return $url;
      }

      // File is not an image
      if (!self::isImage($url)) {
        return $url;
      }
    }

    if (self::isExcluded($url)) {
      return $url;
    }

    if (self::isExcludedFrom('cdn', $url)) {
      return $url;
    }

    $webp = '/wp:' . self::$webp;
    if (self::isExcludedFrom('webp', $url)) {
      $webp = '';
    }

    $newUrl = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($url);
    return $newUrl;
  }

  public function replaceImageTagsDo($image)
  {
    if (strpos($_SERVER['REQUEST_URI'], 'embed') !== false) {
      return $image[0];
    }

    if (!empty($_GET['dbgAjax'])) {
      return print_r(array($_SERVER, wp_doing_ajax(), self::$isAjax, $image[0]), true);
    }

    // Woocommerce ajax load more?
    if (strpos($image[0], 'attachment-woocommerce') !== false) {
      return $image[0];
    }

    if (self::$isAjax) {
      $AjaxImage = $this->ajaxImage($image[0]);
      return $AjaxImage;
    }

    //fixes images not loading in shop pagination on some woo themes
    if (strpos($_SERVER['REQUEST_URI'], 'pjax=') !== false) {
      self::$lazyEnabled = '0';
      self::$adaptiveEnabled = '0';
    }

    if (strpos($image[0], 'data:image') !== false || strpos($image[0], 'blank') !== false) {
      return $image[0];
    }

    self::$lazyLoadedImages++;

    $skipLazy = false;
    $isLogo = false;
    $isSlider = false;

    if (strpos($image[0], self::$zoneName) == false) {
      // File has already been replaced
      if ($this->defaultExcluded($image[0])) {
        return $image[0];
      }

      // File is not an image
      if (!self::isImage($image[0])) {
        return $image[0];
      }

      if ((self::$externalUrlEnabled == 'false' || self::$externalUrlEnabled == '0') && !self::imageUrlMatchingSiteUrl($image[0])) {
        return $image[0];
      }

    } else {
      // Already has zapwp url, if minify:false/true then it's something
      if (strpos($image[0], 'm:') !== false) {
        return $image[0];
      }
    }

    // Something for cookie??
    if (strpos($image[0], 'cookie') !== false) {
      $image[0] = stripslashes($image[0]);
      return $image[0];
    }


    // Original URL was
    $original_img_tag = array();
    $original_img_tag['original_tags'] = $this->getAllTags($image[0], []);

    if (!empty($_GET['dbg_img'])) {
      return print_r([$image[0], $original_img_tag['original_tags']], true);
    }

    /**
     * strpos blank is required to make it work when image has placeholder containing "blank" in it.
     */
    if (!empty($original_img_tag['original_tags']['src'])) {
      $image_source = $original_img_tag['original_tags']['src'];
    } else {
      if (!empty($original_img_tag['original_tags']['data-src'])) {
        $image_source = $original_img_tag['original_tags']['data-src'];
      } elseif (!empty($original_img_tag['original_tags']['data-cp-src'])) {
        $image_source = $original_img_tag['original_tags']['data-cp-src'];
      }
    }

    if (!empty($original_img_tag['original_tags']['data-src'])) {
      $image_source = $original_img_tag['original_tags']['data-src'];
    }


    /*
     * Patch for Image Src in JSON
     * data-mk-image-src-set
     */
    if (!empty($original_img_tag['original_tags']['data-mk-image-src-set'])) {
      $jsonString = htmlspecialchars_decode($original_img_tag['original_tags']['data-mk-image-src-set']);
      $decodedArray = json_decode($jsonString, true);
      if (!empty($decodedArray['default'])) {
        $image_source = $decodedArray['default'];
      }
    }


    if (self::isExcludedFrom('cdn', $image_source)) {
      return $image[0];
    }

    if (!empty($_GET['dbg_img_src'])) {
      return print_r(array('src_is_empty' => empty($original_img_tag['original_tags']['src']), 'data-src_is_empty' => empty($original_img_tag['original_tags']['data-src']), 'data-cp-src_is_empty' => empty($original_img_tag['original_tags']['data-cp-src']), 'src' => $image_source, 'tags' => $original_img_tag), true);
    }

    $original_img_tag['original_src'] = $image_source;

    /**
     * Fetch image actual size
     */
    if (!empty($original_img_tag['original_tags']['width'])) {
      $size = array();
      $size[0] = $original_img_tag['original_tags']['width'];
      $size[1] = $original_img_tag['original_tags']['height'];
    } else {
      $size = self::get_image_size($image_source);
    }

    // SVG Placeholder
    $source_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="' . $size[0] . '" height="' . $size[1] . '"><path d="M2 2h' . $size[0] . 'v' . $size[1] . 'H2z" fill="#fff" opacity="0"/></svg>');

    $image_source = $this->specialChars($image_source);

    if (self::$isAmp->isAmp()) {
      $source_svg = $image_source;
      self::$lazyEnabled = '0';
      self::$adaptiveEnabled = '0';
    }

    if (isset($_GET['preload']) && !empty($_GET['preload'])) {
      $source_svg = $image_source;
      self::$lazyEnabled = '0';
      self::$adaptiveEnabled = '0';
    }

    if (empty($original_img_tag['original_tags']['class']) || !isset($original_img_tag['original_tags']['class'])) {
      $original_img_tag['original_tags']['class'] = '';
    }

    if (empty($original_img_tag['class']) || !isset($original_img_tag['class'])) {
      $original_img_tag['class'] = '';
    }

    if (!empty($original_img_tag['class']) && strpos($original_img_tag['class'], 'kb-img') !== false) {
      $original_img_tag['class'] = '';
    }

    if (strpos(strtolower($original_img_tag['original_tags']['class']), 'slide') !== false || strpos(strtolower($original_img_tag['class']), 'slide') !== false) {
      $source_svg = $image_source;
      $isSlider = true;
    }

    $imageUrl = strtolower($image_source);

    if (strpos(strtolower($imageUrl), 'logo') !== false || (!empty($original_img_tag['class']) && strpos(strtolower($original_img_tag['original_tags']['class']), 'logo')) !== false) {
      if (strpos(strtolower($imageUrl), 'wordpress') === false) {
        $isLogo = true;
      }
    }

    if (!empty($original_img_tag['sizes'])) {
      $original_img_tag['additional_tags']['sizes'] = $original_img_tag['sizes'];
    }

    if (!empty($_GET['dbg_logo'])) {
      return print_r([$image_source], true);
    }

    if (!empty($_GET['dbg_tags'])) {
      return print_r(array($original_img_tag), true);
    }


    $webp = '/wp:' . self::$webp;
    if (self::$excludes_class->isWebpExcluded($image_source, $original_img_tag['original_tags']['class'])) {
      $webp = '/wp:0';
      $original_img_tag['original_tags']['class'] .= ' wpc-excluded-webp';
      $original_img_tag['additional_tags']['wpc-data'] = 'excluded-webp ';
    }

    if (self::$excludes_class->isLazyExcluded($image_source, $original_img_tag['original_tags']['class'])) {
      $original_img_tag['additional_tags']['wpc-data'] = 'excluded-lazy ';
      $isLogo = true;
    }

    $original_img_tag['additional_tags']['data-wpc-loaded'] = 'true';


    // Is LazyLoading enabled in the plugin?
    if (!$isSlider && !empty(self::$lazyEnabled) && self::$lazyEnabled == '1' && !self::$lazyOverride) {
      // if image is logo, then force image url - no lazy loading
      if ($isLogo) {
        // TODO: This is a fix for logo not being on CDN
        $original_img_tag['src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth('logo') . '/u:' . self::reformatUrl($image_source);
        $original_img_tag['original_tags']['src'] = $original_img_tag['src'];
        $original_img_tag['additional_tags']['class'] = 'wps-ic-live-cdn wps-ic-logo';
      } else if (self::$lazyLoadedImages <= self::$lazyLoadSkipFirstImages) {
        // Don't lazy load LCP Fix !!
        // If we loaded less images than skip first variable
        $original_img_tag['src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth('logo') . '/u:' . self::reformatUrl($image_source);
        $original_img_tag['original_tags']['src'] = $original_img_tag['src'];
        $original_img_tag['additional_tags']['class'] = 'wps-ic-live-cdn';
      } else {
        if (self::$lazyLoadedImages > self::$lazyLoadedImagesLimit) {
          // We are over lazy limit, load placeholder
          $original_img_tag['src'] = $source_svg;
          $original_img_tag['data-src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($image_source);
          $original_img_tag['additional_tags']['class'] = 'wps-ic-live-cdn wps-ic-lazy-image';
          $original_img_tag['additional_tags']['loading'] = 'lazy';
        } else {
          // We are under lazy limit, load image
          $original_img_tag['src'] = $source_svg;
          $original_img_tag['data-src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($image_source);
          $original_img_tag['additional_tags']['class'] = 'wps-ic-live-cdn wps-ic-lazy-image wps-ic-loaded';
        }

        // Data cp-src
        if (!empty($original_img_tag['original_tags']['data-cp-src'])) {
          $original_img_tag['original_tags']['data-cp-src'] = $original_img_tag['data-src'];
        }
      }
    } else {
      // We enter this if "isLOGO" == true because of lazy disabled
      if (!$isSlider && !empty(self::$adaptiveEnabled) && self::$adaptiveEnabled == '1') {
        $original_img_tag['src'] = $source_svg;
        $original_img_tag['additional_tags']['class'] = 'wps-ic-cdn';

        /**
         * If current image is logo then force image, don't lazy load
         */
        if ($isLogo || strpos(strtolower($image_source), 'logo') !== false) {
          // TODO: Fix for logos not on CDN
          $original_img_tag['src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($image_source);
          $original_img_tag['original_tags']['src'] = $original_img_tag['src'];
        } else {
          $original_img_tag['src'] = $source_svg;
          $original_img_tag['data-src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($image_source);

          // Data cp-src
          if (!empty($original_img_tag['original_tags']['data-cp-src'])) {
            $original_img_tag['original_tags']['data-cp-src'] = $original_img_tag['data-src'];
          }

        }
      } else {
        // Adaptive is Disabled
        $original_img_tag['additional_tags']['class'] = 'wps-ic-cdn';

        if (strpos($original_img_tag['original_tags']['class'], 'lazy') !== false) {
          if (!empty($original_img_tag['original_tags']['data-src'])) {
            $original_img_tag['data-src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($original_img_tag['original_tags']['data-src']);
          } else {
            $original_img_tag['data-src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($image_source);
          }

          $original_img_tag['original_tags']['src'] = $original_img_tag['data-src'];
          $original_img_tag['original_tags']['data-src'] = $original_img_tag['data-src'];
          $original_img_tag['src'] = $original_img_tag['data-src'];

          // Data cp-src
          if (!empty($original_img_tag['original_tags']['data-cp-src'])) {
            $original_img_tag['original_tags']['data-cp-src'] = $original_img_tag['data-src'];
          }
        } else {
          $original_img_tag['src'] = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($image_source);

          // Data cp-src
          if (!empty($original_img_tag['original_tags']['data-cp-src'])) {
            $original_img_tag['original_tags']['data-cp-src'] = $original_img_tag['src'];
          }
        }
      }
    }


    // Lazy Loading - Fix for LCP Lazy Issues
    if (self::$lazyLoadedImages <= self::$lazyLoadSkipFirstImages) {
      $skipLazy = true;
      $original_img_tag['additional_tags']['fetchpriority'] = 'high';
    }


    if (!empty($_GET['dbg_tag'])) {
      return print_r(array('$isLogo' => $isLogo, 'skipLazy' => $skipLazy, 'adaptiveEnabled' => self::$adaptiveEnabled, '$lazyLoadedImages' => self::$lazyLoadedImages, '$lazyLoadedImagesLimit' => self::$lazyLoadedImagesLimit, '$lazyEnabled' => self::$lazyEnabled, '$nativeLazyEnabled' => self::$nativeLazyEnabled, '$isSlider' => $isSlider, '$original_img_tag' => $original_img_tag), true);
    }

    // PerfMatters Fix for lazy loading
    if (self::$perfMattersActive) {
      if (!empty($original_img_tag['data-src'])) {
        $original_img_tag['original_tags']['src'] = $original_img_tag['data-src'];
        $original_img_tag['src'] = $original_img_tag['data-src'];
        unset($original_img_tag['data-src']);
      }
    }

    if (empty($original_img_tag['original_tags']['srcset']) || !isset($original_img_tag['original_tags']['srcset'])) {
      $original_img_tag['original_tags']['srcset'] = '';
    }

    # if (!empty(self::$adaptiveEnabled) && self::$adaptiveEnabled == '0') {
    if (!self::$excludes_class->isAdaptiveExcluded($image_source, $original_img_tag['original_tags']['class'])) {
      // TODO: Hrvoje testing flickering
      $original_img_tag['original_tags']['data-srcset'] = $this->rewriteSrcset($original_img_tag['original_tags']['srcset']);
      // TODO: Working?
      #$original_img_tag['original_tags']['srcset'] = $this->rewriteSrcset($original_img_tag['original_tags']['srcset']);
    } else {
      // TODO: For some reason this was commented out (class)
      $original_img_tag['original_tags']['class'] .= ' wpc-excluded-adaptive';
      $original_img_tag['additional_tags']['wpc-data'] = 'excluded-adaptive ';
    }
    #}

    unset($original_img_tag['original_tags']['srcset']);


    $build_image_tag = '<img ';

    // Patch, remove things
    unset($original_img_tag['original_tags']['fetchpriority'], $original_img_tag['original_tags']['decoding']);


    //Is native lazy enabled?
    if (!empty(self::$nativeLazyEnabled) && self::$nativeLazyEnabled == '1') {
      if (!$skipLazy && !$isLogo) {
        if (!self::$lazyOverride && !self::isExcludedFrom('lazy', $image_source)) {
          if (strpos($original_img_tag['original_tags']['class'], 'rs') === false) {
            $build_image_tag .= 'loading="lazy" ';
          }
        }
      }
    }

    if (!empty($original_img_tag['original_src'])) {
      $original_img_tag['original_src'] = $this->specialChars($original_img_tag['original_src']);
    }

    if (!empty($original_img_tag['src'])) {
      $original_img_tag['src'] = $this->specialChars($original_img_tag['src']);
    }

    if (!empty($original_img_tag['original_tags']['data-src'])) {
      $original_img_tag['original_tags']['data-src'] = $this->specialChars($original_img_tag['original_tags']['data-src']);
    }

    if (!empty($original_img_tag['data-src'])) {
      $original_img_tag['data-src'] = $this->specialChars($original_img_tag['data-src']);
    }

    if (self::isExcluded($original_img_tag['original_src'], $original_img_tag['original_src'])) {
      // Image is excluded
      if (!empty($original_img_tag['original_src'])) {
        $original_img_tag['src'] = $original_img_tag['original_src'];
      } elseif (!empty($original_img_tag['data-src'])) {
        $original_img_tag['src'] = $original_img_tag['data-src'];
      }
    }

    /**
     * Is this image lazy excluded?
     */

    if (!empty(self::$lazyEnabled) && self::$lazyEnabled == '1') {
      if (self::$excludes_class->isLazyExcluded($image_source, $original_img_tag['original_tags']['class'])) {
        //Don't add anything if lazy load is off
        $original_img_tag['src'] = $image_source;
      }
    }

    if ($isLogo || !empty(self::$removeSrcset) && self::$removeSrcset == '1') {
      unset($original_img_tag['original_tags']['srcset'], $original_img_tag['original_tags']['data-srcset']);
    }

    if (!empty($_GET['remove_srcset'])) {
      unset($original_img_tag['original_tags']['srcset'], $original_img_tag['original_tags']['data-srcset']);
    }

    if (!empty($_GET['test_adaptive'])) {
      if (!empty(self::$adaptiveEnabled) && self::$adaptiveEnabled == '1') {
        $build_image_tag .= 'data-src="' . $original_img_tag['data-src'] . '" ';
        $original_img_tag['original_tags']['data-src'] = $source_svg;
      }
    }

    if (empty($original_img_tag['data-src'])) {
      $original_img_tag['data-src'] = '';
    }

    /**
     * If image contains logo in filename, then it's a logo probably
     */
    if (strpos(strtolower($original_img_tag['original_tags']['class']), 'rs-lazyload') !== false || strpos(strtolower($original_img_tag['original_tags']['class']), 'rs') !== false || strpos(strtolower($image_source), 'logo') !== false || strpos(strtolower($original_img_tag['class']), 'logo') !== false) {
      $build_image_tag .= 'src="' . $original_img_tag['original_tags']['src'] . '" ';
    } else {
      /*
         * if data-src is not empty then we have src as SVG
         */
      if (!empty(self::$lazyEnabled) && self::$lazyEnabled == '1') {
        $build_image_tag .= 'src="' . $original_img_tag['src'] . '" ';
        $build_image_tag .= 'data-src="' . $original_img_tag['data-src'] . '" ';
      } elseif (!empty(self::$adaptiveEnabled) && self::$adaptiveEnabled == '1') {
        $build_image_tag .= 'src="' . $original_img_tag['src'] . '" ';
        $build_image_tag .= 'data-src="' . $original_img_tag['data-src'] . '" ';
      } else {
        if (!empty($original_img_tag['original_tags']['data-src'])) {
          $build_image_tag .= 'src="' . $original_img_tag['original_tags']['data-src'] . '" ';
        } else {
          if (!empty($original_img_tag['data-src'])) {
            $build_image_tag .= 'src="' . $original_img_tag['data-src'] . '" ';
          } else {
            $build_image_tag .= 'src="' . $original_img_tag['src'] . '" ';
          }
        }
      }
    }

    if (!empty($original_img_tag['original_tags'])) {
      foreach ($original_img_tag['original_tags'] as $tag => $value) {
        if (empty($value) || $tag == 'class' || $tag == 'src' || $tag == 'data-src' || $tag == 'data-mk-image-src-set' || $tag == 'data-prehidden') {
          continue;
        }

        $build_image_tag .= $tag . '="' . $value . '" ';
      }
    }

    // foreach additional image tag
    foreach ($original_img_tag['additional_tags'] as $tag => $value) {
      if ($tag == 'class') {
        $tag = 'class';

        if (strpos($original_img_tag['original_tags']['class'], 'rs-lazyload') !== false || strpos($original_img_tag['original_tags']['class'], 'rs') !== false || (strpos($original_img_tag['original_tags']['class'], 'lazy') !== false && strpos($original_img_tag['original_tags']['class'], 'skip-lazy') === false)) {
          // Leave as is
          $value = $original_img_tag['original_tags']['class'];
        } else {
          $value .= ' ' . $original_img_tag['original_tags']['class'];
        }
      }

      if ($tag == 'src' || $tag == 'data-src' || $tag == 'data-mk-image-src-set' || empty($value) || $tag == 'data-prehidden') {
        continue;
      }

      // Check if tag already exists, if so - replace it
      $value = trim($value);
      $build_image_tag .= $tag . '="' . $value . '" ';
    }

    if (empty($original_img_tag['original_tags']['alt'])) {
      $original_img_tag['original_tags']['alt'] = '';
    }

    $build_image_tag .= 'alt="' . $original_img_tag['original_tags']['alt'] . '" ';

    $build_image_tag .= '/>';

    if (!empty($_GET['dbgAjaxEnd'])) {
      return print_r(array($_POST, $_GET, wp_doing_ajax(), self::$isAjax, $image[0]), true);
    }

    if (!empty($_GET['dbg_buildimg'])) {
      return print_r([$original_img_tag['original_tags'], $original_img_tag['additional_tags'], str_replace('<img', 'mgi', $build_image_tag)], true);
    }

    if (self::$isAjax) {
      $build_image_tag = addslashes($build_image_tag);
    }

    return $build_image_tag;
  }

  public function ajaxImage($imageElement)
  {
    if ($this->checkIsSlashed($imageElement)) {
      $imageElement = stripslashes($imageElement);
    }

    $newImageElement = '';
    $original_img_tag = array();
    $original_img_tag['original_tags'] = $this->getAllTags($imageElement, []);

    if (!empty($_GET['ajaxImage'])) {
      return print_r(array($original_img_tag, $imageElement), true);
    }

    if (strpos($original_img_tag['original_tags']['src'], 'data:image') !== false || strpos($original_img_tag['original_tags']['src'], 'blank') !== false) {

      $newImageElement = '<img ';
      // it's placeholder or blank file change something
      foreach ($original_img_tag['original_tags'] as $tag => $value) {
        if ($tag == 'src') {
          // Do nothing
        } elseif ($tag == 'data-src') {
          $src = $value;

          $webp = '/wp:' . self::$webp;
          if (self::isExcludedFrom('webp', $src)) {
            $webp = '/wp:0';
          }

          $src = self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $this::getCurrentMaxWidth(1) . '/u:' . self::reformatUrl($src);
          $newImageElement .= 'src="' . $src . '" ';
        } else {
          $newImageElement .= $tag . '="' . $value . '" ';
        }
      }
      $newImageElement .= '/>';
    } else {
      $newImageElement = $imageElement;
    }

    if ($this->checkIsSlashed($imageElement)) {
      $newImageElement = stripslashes($newImageElement);
    }

    return $newImageElement;
  }

  public static function get_image_size($url)
  {
    preg_match("/([0-9]+)x([0-9]+)\.[a-zA-Z0-9]+/", $url, $matches); //the filename suffix way
    if (isset($matches[1]) && isset($matches[2])) {
      return [$matches[1], $matches[2]];
    } else { //the file
      return [1024, 1024];
    }
  }

  public function rewriteSrcset($srcset)
  {
    if (!empty($srcset)) {
      $newSrcSet = '';
      preg_match_all('/((https?\:\/\/|\/\/)[^\s]+\S+\.(jpg|jpeg|png|gif|svg|webp))\s(\d{1,5}+[wx])/', $srcset, $srcset_links);

      if (!empty($srcset_links)) {
        foreach ($srcset_links[0] as $i => $srcset) {
          $src = explode(' ', $srcset);
          $srcset_url = $src[0];
          $srcset_width = $src[1];

          $webp = '/wp:' . self::$webp;
          if (self::isExcludedFrom('webp', $srcset_url)) {
            $webp = '';
          }

          if (self::isExcludedLink($srcset_url)) {
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

            if (strpos($srcset_url, self::$zoneName) !== false) {
              $newSrcSet .= $srcset_url . ' ' . $srcset_width . $extension . ',';
              continue;
            }

            if (strpos($srcset_url, '.svg') !== false) {
              $newSrcSet .= 'https://' . self::$zoneName . '/m:0/a:' . self::reformatUrl($srcset_url) . ' ' . $srcset_width . $extension . ',';
            } else {
              $newSrcSet .= self::$apiUrl . '/r:' . self::$isRetina . $webp . '/w:' . $width_url . '/u:' . self::reformatUrl($srcset_url) . ' ' . $srcset_width . $extension . ',';
            }
          }
        }

        $newSrcSet = rtrim($newSrcSet, ',');
      }

      return $newSrcSet;
    }

    return $srcset;
  }

}