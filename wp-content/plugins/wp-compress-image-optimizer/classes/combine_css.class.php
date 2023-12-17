<?php

include_once WPS_IC_DIR . 'addons/cdn/cdn-rewrite.php';
include_once WPS_IC_DIR . 'traits/url_key.php';

class wps_ic_combine_css
{

  public static $excludes;
  public static $rewrite;
  public static $isMobile;

  public $zone_name;

  public function __construct()
  {
    $this->url_key_class = new wps_ic_url_key();
    $this->urlKey = $this->url_key_class->setup();
    $this->combined_dir = WPS_IC_COMBINE . $this->urlKey . '/css/';
    $this->combined_url_base = WPS_IC_COMBINE_URL . $this->urlKey . '/css/';

    $this::$isMobile = $this->isMobile();

    $this->firstFoundStyle = false;

    self::$excludes = new wps_ic_excludes();
    self::$rewrite = new wps_cdn_rewrite();
    $this->settings = get_option(WPS_IC_SETTINGS);
    #$this->filesize_cap           = '500000'; //in bytes
    $this->filesize_cap = '100000000000'; //in bytes
    $this->combine_inline_scripts = false;
    $this->combine_external = false;
    $this->allExcludes = self::$excludes->combineCSSExcludes();


    if (!empty($_GET['criticalCombine'])) {
      $this->criticalCombine = true;
      $this->filesize_cap = '10000000000'; //in bytes
      $this->combine_inline_scripts = true;
      $this->combine_external = true;
      // TODO: Denis this was causing issues with rev slider 6 because it removes the <style> tag which rev slider uses to transfer options,settings into javascript, which then is used to control the settings of rev slider 6. Maybe we leave it ON with deafult excludes as it will probably be requried for many other plugins,sliders,themes in future. Critical CSS is broken in case some CSS is missing.
      $this->allExcludes = ['media="print"', 'media=\'print\''];
      #$this->allExcludes            = self::$excludes->combineCSSExcludes();
    }

    $this->patterns = [
      '/<link[^>]*rel=[\"|\']stylesheet[\"|\'][^>]*>/si',
      '/(?<!<noscript>)<style\b[^>]*\>(.*?)<\/style\>?/si',
      '/<link\b[^>](.*?)onload=[\"|\']this.rel=[\"|\']stylesheet[\"|\'][\"|\'](.*?)>/' // deferred stylesheets
    ];

    $custom_cname = get_option('ic_custom_cname');
    if (empty($custom_cname) || !$custom_cname) {
      $this->zone_name = get_option('ic_cdn_zone_name');
    } else {
      $this->zone_name = $custom_cname;
    }

    //Check if Hide my WP is active and get replaces
    $this->hmwpReplace = false;
    if (class_exists('HMWP_Classes_ObjController')) {
      $this->hmwpReplace = true;
      $plugin_path = WP_PLUGIN_DIR . '/hide-my-wp/';
      include_once($plugin_path . 'classes/ObjController.php');
      $hmwp_controller = new HMWP_Classes_ObjController;
      $this->hmwp_rewrite = $hmwp_controller::getClass('HMWP_Models_Rewrite');
    }
  }


  public function isMobile()
  {
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
      if (preg_match('#^.*(2.0\ MMP|240x320|400X240|mobile|AvantGo|BlackBerry|Blazer|Cellphone|Danger|DoCoMo|Elaine/3.0|EudoraWeb|Googlebot-Mobile|hiptop|IEMobile|KYOCERA/WX310K|LG/U990|MIDP-2.|MMEF20|MOT-V|NetFront|Newt|Nintendo\ Wii|Nitro|Nokia|Opera\ Mini|Palm|PlayStation\ Portable|portalmmm|Proxinet|ProxiNet|SHARP-TQ-GX10|SHG-i900|Small|SonyEricsson|Symbian\ OS|SymbianOS|TS21i-10|UP.Browser|UP.Link|webOS|Windows\ CE|WinWAP|YahooSeeker/M1A1-R2D2|iPhone|iPod|Android|BlackBerry9530|LG-TU915\ Obigo|LGE\ VX|webOS|Nokia5800).*#i', $agent) || preg_match('#^(w3c\ |w3c-|acs-|alav|alca|amoi|audi|avan|benq|bird|blac|blaz|brew|cell|cldc|cmd-|dang|doco|eric|hipt|htc_|inno|ipaq|ipod|jigs|kddi|keji|leno|lg-c|lg-d|lg-g|lge-|lg/u|maui|maxo|midp|mits|mmef|mobi|mot-|moto|mwbp|nec-|newt|noki|palm|pana|pant|phil|play|port|prox|qwap|sage|sams|sany|sch-|sec-|send|seri|sgh-|shar|sie-|siem|smal|smar|sony|sph-|symb|t-mo|teli|tim-|tosh|tsm-|upg1|upsi|vk-v|voda|wap-|wapa|wapi|wapp|wapr|webc|winw|winw|xda\ |xda-).*#i', substr($agent, 0, 4))) {
        return true;
      }
    }

    return false;
  }


  public function maybe_do_combine($html)
  {

    // Disabled for some reason?!
    if (1 == 0 && $this->combine_exists() && (empty($_GET['forceRecombine']) && !$this->criticalCombine)) {
      $this->no_content_excludes = get_option('wps_no_content_excludes_css');
      if ($this->no_content_excludes !== false) {
        $this->allExcludes = array_merge($this->allExcludes, $this->no_content_excludes);
      }

      $html = $this->replace($html);
      return $html;
    }


    $this->no_content_excludes = [];
    $this->current_file = '';
    $this->file_count = 1;

    $this->setup_dirs();

    $this->current_section = 'header';
    $html = preg_replace_callback('/<head(.*?)<\/head>/si', [$this, 'combine'], $html);

    if (!$this->criticalCombine) {
      //we want 1 file in criticalCombine so we dont do this
      $this->write_file_and_next();
      $this->current_section = 'footer';
      $this->file_count = 1;
    }

    $html = preg_replace_callback('/<\/head>(.*?)<\/body>/si', [$this, 'combine'], $html);

    $this->write_file_and_next();

    update_option('wps_no_content_excludes_css', $this->no_content_excludes);
    $html = $this->insert_combined_scripts($html);

    return $html;
  }

  public function combine_exists()
  {
    $exists = is_dir($this->combined_dir);
    if ($exists) {
      $exists = (new \FilesystemIterator($this->combined_dir))->valid();
    }

    return $exists;
  }

  public function replace($html)
  {

    $html = preg_replace_callback($this->patterns, array($this, 'remove_scripts'), $html);
    $html = $this->insert_combined_scripts($html);

    return $html;
  }

  public function insert_combined_scripts($html)
  {

    $combined_files = new \FilesystemIterator($this->combined_dir);

    if ($this->criticalCombine) {
      foreach ($combined_files as $file) {
        $url = $this->combined_url_base . basename($file);
        $link = '<link rel="stylesheet" id="wpc-critical-combined-css" href="' . $url . '" type="text/css" media="all">' . PHP_EOL;
      }

      $html = str_replace('<!--WPC_INSERT_COMBINED_CSS-->', $link, $html);
      return $html;
    }

    $header_links = '';
    $footer_links = '';

    foreach ($combined_files as $file) {
      $url = $this->combined_url_base . basename($file);

      $criticalCSS = new wps_criticalCss();

      if (!empty($this->settings['critical']['css']) && $this->settings['critical']['css'] == '1' && $criticalCSS->criticalExists() !== false) {

        // Critical CSS Option is Enabled
        if (strpos($file, 'wps_header') !== false) {
          $header_links .= '<link rel="wpc-stylesheet" href="' . self::$rewrite->adjust_src_url($url) . '" type="wpc-stylesheet" media="all">' . PHP_EOL;
        } else {
          $footer_links .= '<link rel="wpc-stylesheet" href="' . self::$rewrite->adjust_src_url($url) . '" type="wpc-stylesheet" media="all">' . PHP_EOL;
        }

      } else if (!empty($this->settings['remove-render-blocking']) && $this->settings['remove-render-blocking'] == '1') {

        // Remove render blocking option is Enabled
        if (strpos($file, 'wps_header') !== false) {
          $header_links .= '<link rel="preload" as="style"  onload="this.rel=\'stylesheet\'" defer href="' . $url . '" type="text/css" media="all">' . PHP_EOL;
        } else {
          $footer_links .= '<link rel="preload" as="style"  onload="this.rel=\'stylesheet\'" defer href="' . $url . '" type="text/css" media="all">' . PHP_EOL;
        }

      } else if (!empty($this->settings['inline-css']) && $this->settings['inline-css'] == '1') {

        // Inline CSS Option is Enabled
        if (strpos($file, 'wps_header') !== false) {
          $combineContent = file_get_contents($file->getPathname());

          if (!empty($combineContent)) {
            $header_links .= '<style type="text/css" id="' . basename($file) . '">';
            $header_links .= $this->minifyCss($combineContent);
            $header_links .= '</style>';
          }

        } else {
          $combineContent = file_get_contents($file->getPathname());

          if (!empty($combineContent)) {
            $footer_links .= '<style type="text/css" id="' . basename($file) . '">';
            $footer_links .= $this->minifyCss($combineContent);
            $footer_links .= '</style>';
          }
        }

      } else {

        // Inline is not enabled, critical is not enabled
        if (strpos($file, 'wps_header') !== false) {
          $header_links .= '<link rel="stylesheet" href="' . self::$rewrite->adjust_src_url($url) . '" type="text/css" media="all">' . PHP_EOL;
        } else {
          $footer_links .= '<link rel="stylesheet" href="' . self::$rewrite->adjust_src_url($url) . '" type="text/css" media="all">' . PHP_EOL;
        }

      }
    }

    if ($this->hmwpReplace) {
      //apply their replacements to our combined files because they are doing them before our insert
      foreach ($this->hmwp_rewrite->_replace['from'] as $key => $value) {
        $replace = $this->hmwp_rewrite->_replace['to'][$key];
        $header_links = str_replace($value, $replace, $header_links);
        $footer_links = str_replace($value, $replace, $footer_links);
      }
    }

    //header
    if (!empty($_GET['testcombine'])) {
      $html = preg_replace('/<\/head>/', $header_links . '</head>', $html);
    } else {
      if (!empty($header_links)) {
        $html = str_replace('<!--WPC_INSERT_COMBINED_CSS-->', $header_links, $html);
      }
    }

    //footer
    $html = preg_replace('/<\/body>/', $footer_links . '</body>', $html);

    return $html;
  }


  public function minifyCss($css)
  {
    if (!empty($this->settings['css_minify']) && $this->settings['css_minify'] == '1') {
      //      // Remove line breaks and multiple spaces
      //      $css = preg_replace('/\s+/', ' ', $css);
      //
      //      // Remove spaces before and after braces
      //      $css = str_replace(array('{ ', ' }'), array('{', '}'), $css);
      //
      //      // Remove spaces before and after colons
      //      $css = str_replace(': ', ':', $css);
      $css = preg_replace('/\/\*(.*?)\*\//s', '', $css); // Remove comments
      $css = preg_replace('/\s+/', ' ', $css); // Remove multiple whitespaces
      $css = preg_replace('/\s?([,:;{}])\s?/', '$1', $css); // Remove spaces around selectors and declarations
      $css = preg_replace('/;}/', '}', $css); // Remove trailing semicolons before closing brace
    } else {
      // Remove line breaks and multiple spaces
      $css = preg_replace('/\s+/', ' ', $css);
    }
    return $css;
  }


  public function setup_dirs()
  {
    mkdir(WPS_IC_COMBINE . $this->urlKey . '/css', 0777, true);
  }

  public function write_file_and_next()
  {

    $prefix = '';
    if (self::$isMobile) {
      $prefix = 'mobile_';
    }

    if ($this->criticalCombine) {
      file_put_contents($this->combined_dir . 'wps_combined.css', $this->current_file);
      return;
    }

    if ($this->current_file != '') {
      file_put_contents($this->combined_dir . 'wps_' . $prefix . $this->current_section . '_' . $this->file_count . '.css', $this->current_file);
    }
    $this->file_count++;
    $this->current_file = '';
  }

  public function combine($html)
  {
    $html = $html[0];
    $html = preg_replace_callback($this->patterns, array($this, 'script_combine_and_replace'), $html);

    return $html;
  }

  public function script_combine_and_replace($tag)
  {
    $tag = $tag[0];
    $src = '';

    if (!empty($_GET['dbgCombine']) && $_GET['dbgCombine'] == 'before') {
      return print_r(array($tag), true);
    }

    // Check if the CSS is Excluded
    if (self::$excludes->strInArray($tag, $this->allExcludes)) {
      if (!empty($_GET['dbgCombine']) && $_GET['dbgCombine'] == 'outputs') {
        return print_r(array($tag, 'excluded'), true);
      }
      return $tag;
    }

    // If it has ie9 tag exclude by default
    if (strpos($tag, 'ie9') !== false) {
      return $tag;
    }


    $is_src_set = preg_match('/href=["|\'](.*?)["|\']/', $tag, $src);

    if (!empty($_GET['dbgCombine']) && $_GET['dbgCombine'] == 'preg') {
      return print_r(array($tag), true);
    }

    if ($is_src_set == 1) {
      $src = str_replace('href=', '', $src);
      $src = str_replace("'", "", $src);
      $src = str_replace('"', "", $src);
      $src = $src[0];

      if (!$this->combine_external && $this->url_key_class->is_external($src)) {
        if (!empty($_GET['dbgCombine']) && $_GET['dbgCombine'] == 'outputs') {
          return print_r(array($tag, 'external'), true);
        }
        return $tag;
      } else if ($this->combine_external && $this->url_key_class->is_external($src)) {
        $content = $this->getRemoteContent($src);
      } else {
        $content = $this->getLocalContent($src);
      }

      if (!$content) {
        $this->no_content_excludes[] = $src;
        if (!empty($_GET['dbgCombine']) && $_GET['dbgCombine'] == 'outputs') {
          return print_r(array($tag, 'no content'), true);
        }
        return $tag;
      }

      //replace relative urls
      $this->asset_url = $src;
      $content = preg_replace_callback("/url(\(((?:[^()])+)\))/i", array(
        $this,
        'rewrite_relative_url'
      ), $content);
    } else if ($this->combine_inline_scripts == true) {
      $src = 'Inline Script';

      $content = $tag;
      $content = preg_replace('/<style(.*?)>/', '', $content, -1, $count);
      $content = preg_replace('/<\/style>/', '', $content);

      if (!$count) {
        //no href, and not a <style> tag
        if (!empty($_GET['dbgCombine']) && $_GET['dbgCombine'] == 'outputs') {
          return print_r(array($tag, 'not a style tag'), true);
        }
        return $tag;
      }
    } else {
      if (!empty($_GET['dbgCombine']) && $_GET['dbgCombine'] == 'outputs') {
        return print_r(array($tag, 'unknown'), true);
      }
      return $tag;
    }


    //sometimes php injects a zero width space char at the start of a new script, this clears it
    $content = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $content);
    $content = str_replace(array('@font-face{', '@font-face {'), '@font-face{font-display: swap;', $content);

    // Find BG and replace with mobile BG
    if ($this::$isMobile) {
      $content = preg_replace_callback("/background-image:\s*url\((.*?)\)/is", array($this, 'changeBgImageToMobile'), $content);
    }

    $this->current_file .= "/* SCRIPT : $src */" . PHP_EOL;
    $this->current_file .= $content . PHP_EOL;

    if (mb_strlen($this->current_file, '8bit') >= $this->filesize_cap) {
      $this->write_file_and_next();
    }

    if (!$this->firstFoundStyle) {
      $this->firstFoundStyle = true;
      return '<!--WPC_INSERT_COMBINED_CSS-->';
    } else {
      return '';
    }
  }


  public function changeBgImageToMobile($html) {
    $bgEntire = $html[0];
    $bgUrl = $html[1];

    $MobileBg = str_replace('m:0/', 'mo:1/', $bgUrl);
    $html = str_replace($bgUrl, $MobileBg, $bgEntire);

    #return print_r(array($bgEntire, $bgUrl, $MobileBg, $html),true);
    return $html;
  }

  function getRemoteContent($url)
  {
    if (strpos($url, '//') === 0) {
      $url = 'https:' . $url;
    }

    $data = wp_remote_get($url);

    //todo Check if file is really css

    if (is_wp_error($data)) {
      return false;
    }

    return wp_remote_retrieve_body($data);
  }

  function getLocalContent($url)
  {
    if ($this->hmwpReplace) {
      //go trougn their replacements and reverse them to get true path to files
      foreach ($this->hmwp_rewrite->_replace['to'] as $key => $value) {
        $replace = $this->hmwp_rewrite->_replace['from'][$key];
        $url = str_replace($value, $replace, $url);
      }
    }

    if (strpos($url, $this->zone_name) !== false) {
      preg_match('/a:(.*?)(\?|$)/', $url, $match);
      $url = trim($match[1]);
    }

    $url = preg_replace('/\?.*/', '', $url);

    $path = wp_make_link_relative($url);
    $path = ltrim($path, '/');

    //check if is folder install and if folder is in url remove it (it is already in ABSPATH)
    $last_abspath = basename(ABSPATH);
    $first_path = explode('/', $path)[0];
    if ($last_abspath == $first_path) {
      $path = substr($path, strlen($first_path));
      $path = ltrim($path, '/');
    }

    $content = file_get_contents(ABSPATH . $path);

    if (!$content) {
      return false;
    }

    return $content;
  }

  public function remove_scripts($tag)
  {
    $tag = $tag[0];
    $src = '';

    if (strpos('rs6', $tag) !== false) {
      return $tag;
    }

    if (!$this->combine_external && $this->url_key_class->is_external($tag)) {
      return $tag;
    }

    if (current_user_can('manage_options') || self::$excludes->strInArray($tag, $this->allExcludes)) {
      return $tag;
    }


    $is_src_set = preg_match('/href=["|\'](.*?)["|\']/', $tag, $src);
    if ($is_src_set == 1) {
      //nothing
    } else if ($this->combine_inline_scripts == true) {
      $src = 'Inline Script';

      $content = $tag;
      $content = preg_replace('/<style(.*?)>/', '', $content, -1, $count);

      if (!$count) {
        //no href, and not a <style> tag
        return $tag;
      }
    } else {
      return $tag;
    }

    if (!$this->firstFoundStyle) {
      $this->firstFoundStyle = true;
      return '<!--WPC_INSERT_COMBINED_CSS-->';
    } else {
      return '';
    }
  }

  function rewrite_relative_url($url)
  {

    $matched_url = $url[2];
    $asset_url = $this->asset_url;
    $matched_url = str_replace('"', '', $matched_url);
    $matched_url = str_replace("'", '', $matched_url);

    $parsed_url = parse_url($asset_url);
    $path = $parsed_url['path'];
    $path = str_replace(basename($path), '', $path);
    $path = ltrim($path, '/');
    $path = rtrim($path, '/');
    $directories = explode('/', $path);

    $host = $parsed_url['host'];
    $scheme = $parsed_url['scheme'];
    $parsed_homeurl = parse_url(get_home_url());

    if (!$host) {
      //relative asset url
      $host = $parsed_homeurl['host'];
    }

    if (!$scheme) {
      //relative asset url
      $scheme = $parsed_homeurl['scheme'];
    }

    if (strpos($matched_url, $this->zone_name) !== false || strpos($matched_url, 'zapwp.net') !== false) {
      return $url[0];
    }

    if (strpos($matched_url, 'google') !== false || strpos($matched_url, 'gstatic') !== false || strpos($matched_url, 'typekit') !== false) {
      return $url[0];
    }

    if (strpos($matched_url, 'data:') !== false) {
      return $url[0];
    }

    $first_char = substr($matched_url, 0, 1);
    if (strpos($matched_url, 'http') === false && ctype_alpha($first_char)) {
      // No,slash.. direct file
      // Same folder
      $relativePath = implode('/', $directories) . '/';
      $matched_url_trim = ltrim($matched_url, './');
      $relativePath .= $matched_url_trim;
      $relativeUrl = $scheme . '://' . $host . '/' . $relativePath;

    } else if (strpos($matched_url, '/') === 0 && strpos($matched_url, '//') !== 0) {
      // Root folder
      $relativePath = '';
      $matched_url_trim = ltrim($matched_url, './');
      $relativePath .= $matched_url_trim;
      $relativeUrl = $scheme . '://' . $host . '/' . $relativePath;

    } else if (strpos($matched_url, './') === 0) {
      // Same folder
      $relativePath = implode('/', $directories) . '/';
      $matched_url_trim = ltrim($matched_url, './');
      $relativePath .= $matched_url_trim;
      $relativeUrl = $scheme . '://' . $host . '/' . $relativePath;

    } else if (strpos($matched_url, '../') === 0) {
      // Are there more directories to go back?
      $exploded_dirs = explode('../', $matched_url);
      array_pop($exploded_dirs);

      foreach ($exploded_dirs as $i => $v) {
        // Back Folder
        array_pop($directories); // Remove 1 last dir
      }
      $relativePath = implode('/', $directories) . '/';
      $matched_url_trim = ltrim($matched_url, '../');
      $relativePath .= $matched_url_trim;

      $relativeUrl = $scheme . '://' . $host . '/' . $relativePath;

    } else {

      // Regular path
      if (strpos($matched_url, 'http://') !== false || strpos($matched_url, 'https://') !== false) {
        // Regular URL
        $replace_url = $matched_url;
      } else {
        // Missing http/s ?
        $replace_url = ltrim($matched_url, '//');
        $replace_url = $scheme . '://' . $replace_url;
      }

      if (strpos($matched_url, '.jpg') !== false || strpos($matched_url, '.png') !== false || strpos($matched_url, '.gif') !== false || strpos($matched_url, '.svg') !== false || strpos($matched_url, '.jpeg') !== false || strpos($matched_url, '.webp') !== false) {
        // Image, put on CDN
        $relativeUrl = $replace_url;
      } else if (strpos($matched_url, '.woff') !== false || strpos($matched_url, '.woff2') !== false || strpos($matched_url, '.ttf') !== false || strpos($matched_url, '.eot') !== false) {

        // Font file, put on site
        $relativeUrl = $replace_url;
      }
    }

    $relativeUrl = trim($relativeUrl);

    if ((strpos($matched_url, '.eot') !== false || strpos($matched_url, '.woff') !== false || strpos($matched_url, '.woff2') !== false || strpos($matched_url, '.ttf') !== false) && $this->settings['serve']['fonts'] == 1) {
      $relativeUrl = 'url("https://' . $this->zone_name . '/m:0/a:' . $relativeUrl . '")';
    } else if ((strpos($matched_url, '.jpg') !== false && $this->settings['serve']['jpg'] == 1) ||
      (strpos($matched_url, '.png') !== false && $this->settings['serve']['png'] == 1) ||
      (strpos($matched_url, '.gif') !== false && $this->settings['serve']['gif'] == 1) ||
      (strpos($matched_url, '.svg') !== false && $this->settings['serve']['svg'] == 1)) {

      if ($this::$isMobile) {
        $relativeUrl = 'url("https://' . $this->zone_name . '/m:0/a:' . $relativeUrl . '")';
      } else {
        $relativeUrl = 'url("https://' . $this->zone_name . '/mo:1/a:' . $relativeUrl . '")';
      }

    } else {
      $relativeUrl = 'url("' . $relativeUrl . '")';
    }

    return $relativeUrl;
  }
}