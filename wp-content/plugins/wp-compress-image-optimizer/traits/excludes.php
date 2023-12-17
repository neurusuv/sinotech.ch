<?php

class wps_ic_excludes extends wps_ic
{

  private static $defaultDelayJSExcludes;
  private static $defaultCombineJSExcludes;
  private static $defaultCombineCSSExcludes;
  private static $defaultCriticalCSSExcludes;
	private static $defaultLazyExcludes;
	private static $defaultWebpExcludes;
	private static $defaultAdaptiveExcludes;
  private static $excludesDelayJSOption;
  private static $excludesCombineJSOption;
  private static $excludesCombineCSSOption;
	private static $excludesToFooterOption;
	private static $excludesLazyOption;
	private static $excludesWebpOption;
	private static $excludesAdaptiveOption;


  // New
  private static $excludesCriticalCSSOption;
  private static $excludesOption;

  public function __construct()
  {
    self::$excludesOption = get_option('wpc-excludes');
		self::$settings = wps_ic::$settings;

    if (!empty(self::$excludesOption['delay_js'])) {
      self::$excludesDelayJSOption = self::$excludesOption['delay_js'];
    }

    if (!empty(self::$excludesOption['combine_js'])) {
      self::$excludesCombineJSOption = self::$excludesOption['combine_js'];
    }

    if (!empty(self::$excludesOption['css_combine'])) {
      self::$excludesCombineCSSOption = self::$excludesOption['css_combine'];
    }

    if (!empty(self::$excludesOption['critical_css'])) {
      self::$excludesCriticalCSSOption = self::$excludesOption['critical_css'];
    }

	  if (!empty(self::$excludesOption['exclude-scripts-to-footer'])) {
		  self::$excludesToFooterOption = self::$excludesOption['exclude-scripts-to-footer'];
	  }

	  if (!empty(self::$excludesOption['lazy'])) {
		  self::$excludesLazyOption = self::$excludesOption['lazy'];
	  }

	  if (!empty(self::$excludesOption['adaptive'])) {
		  self::$excludesAdaptiveOption = self::$excludesOption['adaptive'];
	  }

	  if (!empty(self::$excludesOption['webp'])) {
		  self::$excludesWebpOption = self::$excludesOption['webp'];
	  }

	  self::$defaultLazyExcludes = [
			'show-on-hover'
	  ];

	  self::$defaultAdaptiveExcludes = [

	  ];

	  self::$defaultWebpExcludes = [

	  ];

    self::$defaultDelayJSExcludes = [
      //Our excludes
      'cloudflare',
      'cursus',
      'jqueryParams',
      'initMap',
      'maps',
      'slick',
      'no-delay',
      '_N2',
      'core',
      'slide',
      'swipe',
      'react',
      'dom',
      'imagesloaded',
      'woo',
      'jquery-core',
      'jquery-ui-core',
      #'jquery-migrate',
      'fontawesome',
      'plus-addon',
      'jquery.min.js',
      'jquery.js',
      'lazy.min.js',
      'hooks',
      'lazy',
      'wp-i18',
      'wp.i18',
      'i18',
      'delay-js',
      #'tweenmax',
      'delay-js-script',
      'optimizer',
      //Imported excludes
      'nowprocket',
      '/wp-includes/js/wp-embed.min.js',
      'lazyLoadOptions',
      'lazyLoadThumb',
      'wp-rocket/assets/js/lazyload/',
      'et_core_page_resource_fallback',
      'window.\$us === undefined',
      'js-extra',
      'fusionNavIsCollapsed',
      '/assets/js/smush-lazy-load', // Smush & Smush Pro.
      'eio_lazy_vars',
      '\/lazysizes(\.min|-pre|-post)?\.js', // lazyload library (used in EWWW, Autoptimize, Avada).
      'document\.body\.classList\.remove\("no-js"\)',
      'document\.documentElement\.className\.replace\( \'no-js\', \'js\' \)',
      'et_animation_data',
      'wpforms_settings',
      'var nfForms',
      '//stats.wp.com', // Jetpack Stats.
      '_stq.push', // Jetpack Stats.
      'fluent_form_ff_form_instance_', // Fluent Forms.
      'cpLoadCSS', // Convert Pro.
      'ninja_column_', // Ninja Tables.
      'var rbs_gallery_', // Robo Gallery.
      'var lepopup_', // Green Popup.
      'var billing_additional_field', // Woo Autocomplete Nish.
      'var gtm4wp',
      'var dataLayer_content',
      '/ewww-image-optimizer/includes/load', // EWWW WebP rewrite external script.
      '/ewww-image-optimizer/includes/check-webp', // EWWW WebP check external script.
      'ewww_webp_supported', // EWWW WebP inline scripts.
      '/dist/js/browser-redirect/app.js', // WPML browser redirect script.
      '/perfmatters/js/lazyload.min.js',
      'lazyLoadInstance',
      'scripts.mediavine.com/tags/', // allows mediavine-video schema to be accessible by search engines.
      'initCubePortfolio', // Cube Portfolio show images.
      'simpli.fi', // simpli.fi Advertising Platform scripts.
      'gforms_recaptcha_', // Gravity Forms recaptcha.
      '/jetpack-boost/vendor/automattic/jetpack-lazy-images/', // Jetpack Boost plugin lazyload.
      'jetpack-lazy-images-js-enabled',  // Jetpack Boost plugin lazyload.
      'jetpack-boost-critical-css', // Jetpack Boost plugin critical CSS.
      'wpformsRecaptchaCallback', // WPForms reCAPTCHA v2.
      'booking-suedtirol-js', // bookingsuedtirol.com widgets.
      'wpcp_css_disable_selection', // WP Content Copy Protection & No Right Click.
      '/gravityforms/js/conditional_logic.min.js', // Gravity forms conditions.
      'statcounter.com/counter/counter.js', // StatsCounter.
      'var sc_project', // Statscounter.
      '/jetpack/jetpack_vendor/automattic/jetpack-lazy-images/', // Jetpack plugin lazyload.
      '/themify-builder/themify/js/modules/fallback',
      'handlePixMessage',
      'var corner_video',
      'cdn.pixfuture.com/hb_v2.js',
      'cdn.pixfuture.com/pbix.js',
      'served-by.pixfuture.com/www/delivery/ads.js',
      'served-by.pixfuture.com/www/delivery/headerbid_sticky_refresh.js',
      'serv-vdo.pixfuture.com/vpaid/ads.js',
      'wprRemoveCPCSS',
      'window.jdgmSettings', // Judge.me plugin.
      '/photonic/include/js/front-end/nomodule/photonic-baguettebox.min.js', // Photonic plugin.
      '/photonic/include/ext/baguettebox/baguettebox.min.js', // Photonic plugin.
      'window.wsf_form_json_config', // WSF Form plugin
      'amelia-booking'
    ];

    self::$defaultCombineJSExcludes = [
			'visitor_mode.min.js',
      'jquery.min.js',
      'jquery.js',
      'jquery-migrate',
      'lazy.min.js',
      'wp-i18',
      'wp.i18',
      'dashicon',
      'i18',
      'hooks',
      'lazy',
      'all',
      'optimizer',
      'delay-js',
      'application/ld+json'
    ];

    self::$defaultCombineCSSExcludes = [
      #'responsive', //responsive stuff
      'dashicons',
      'wps-inline', //our inline CSS option
	    'wpc-critical-css', //our critical
	    'wpc-critical-css-mobile', //our mobile critical
	    'rs-plugin', // revolution slider causing JS errors if inline is missing
	    'rs-plugin-settings-inline-css', // revolution slider causing JS errors if inline is missing
	    'media="print"', 'media=\'print\'' //styles only for printing
    ];

    self::$defaultCriticalCSSExcludes = [
    ];

    //Check if default excludes are disabled
    if (!empty(self::$excludesOption['delay_js_default_excludes_disabled']) && self::$excludesOption['delay_js_default_excludes_disabled'] == '1') {
      self::$defaultDelayJSExcludes = array();
    }

    if (!empty(self::$excludesOption['js_combine_default_excludes_disabled']) && self::$excludesOption['js_combine_default_excludes_disabled'] == '1') {
      self::$defaultCombineJSExcludes = array();
    }
    if (!empty(self::$excludesOption['css_combine_default_excludes_disabled']) && self::$excludesOption['css_combine_default_excludes_disabled'] == '1') {
      self::$defaultCombineCSSExcludes = array();
    }

    if (!empty(self::$excludesOption['critical_css_default_excludes_disabled']) && self::$excludesOption['critical_css_default_excludes_disabled'] == '1') {
      self::$defaultCriticalCSSExcludes = array();
    }
  }

	public function scriptsToFooterExcludes()
	{

		if (!empty(self::$excludesToFooterOption) && is_array(self::$excludesToFooterOption)) {
			//something is excluded, so exclude jquery too
			self::$excludesToFooterOption[] = 'jquery';
			return self::$excludesToFooterOption;
		}

		return [];

	}


  public function criticalCSSExcludes()
  {
    if (is_array(self::$excludesCriticalCSSOption)) {
      self::$defaultCriticalCSSExcludes = array_merge(self::$defaultCriticalCSSExcludes, self::$excludesCriticalCSSOption);
    }


	  if (!empty(self::$excludesOption['critical_css_exclude_themes']) &&
	      self::$excludesOption['critical_css_exclude_themes'] == '1')  {
		  self::$defaultCriticalCSSExcludes[] = 'wp-content/themes';
	  }

	  if (!empty(self::$excludesOption['critical_css_exclude_plugins']) &&
	      self::$excludesOption['critical_css_exclude_plugins'] == '1')  {
		  self::$defaultCriticalCSSExcludes[] = 'wp-content/plugins';
	  }

	  if (!empty(self::$excludesOption['critical_css_exclude_wp']) &&
	      self::$excludesOption['critical_css_exclude_wp'] == '1')  {
		  self::$defaultCriticalCSSExcludes[] = 'wp-includes';
	  }

    return self::$defaultCriticalCSSExcludes;
  }


  public function delayJSExcludes()
  {
    if (is_array(self::$excludesDelayJSOption)) {
      self::$defaultDelayJSExcludes = array_merge(self::$defaultDelayJSExcludes , self::$excludesDelayJSOption);
    }

	  if (!empty(self::$excludesOption['delay_js_exclude_themes']) &&
	      self::$excludesOption['delay_js_exclude_themes'] == '1')  {
		  self::$defaultDelayJSExcludes[] = 'wp-content/themes';
	  }

	  if (!empty(self::$excludesOption['delay_js_exclude_plugins']) &&
	      self::$excludesOption['delay_js_exclude_plugins'] == '1')  {
		  self::$defaultDelayJSExcludes[] = 'wp-content/plugins';
	  }

	  if (!empty(self::$excludesOption['delay_js_exclude_wp']) &&
	      self::$excludesOption['delay_js_exclude_wp'] == '1')  {
		  self::$defaultDelayJSExcludes[] = 'wp-includes';
	  }

    return self::$defaultDelayJSExcludes;
  }

  public function combineCSSExcludes()
  {
    if (is_array(self::$excludesCombineCSSOption)) {
      self::$defaultCombineCSSExcludes = array_merge(self::$defaultCombineCSSExcludes , self::$excludesCombineCSSOption);
    }

	  if (!empty(self::$excludesOption['combine_css_exclude_themes']) &&
	      self::$excludesOption['combine_css_exclude_themes'] == '1')  {
		  self::$defaultCombineCSSExcludes[] = 'wp-content/themes';
	  }

	  if (!empty(self::$excludesOption['combine_css_exclude_plugins']) &&
	      self::$excludesOption['combine_css_exclude_plugins'] == '1')  {
		  self::$defaultCombineCSSExcludes[] = 'wp-content/plugins';
	  }

	  if (!empty(self::$excludesOption['combine_css_exclude_wp']) &&
	      self::$excludesOption['combine_css_exclude_wp'] == '1')  {
		  self::$defaultCombineCSSExcludes[] = 'wp-includes';
	  }

	  if ( ! empty( self::$settings['critical']['css'] ) && self::$settings['critical']['css'] == '1' ) {
			//if excluded from crit, it should not be delayed, so it should not be combined either
		  self::$defaultCombineCSSExcludes = array_merge(self::$defaultCombineCSSExcludes, $this->criticalCSSExcludes());
	  }

    return self::$defaultCombineCSSExcludes;
  }

  public function combineJSExcludes()
  {
    if (is_array(self::$excludesCombineJSOption)) {
      self::$defaultCombineJSExcludes = array_merge(self::$defaultCombineJSExcludes, self::$excludesCombineJSOption);
    }

	  if (!empty(self::$excludesOption['combine_js_exclude_themes']) &&
	      self::$excludesOption['combine_js_exclude_themes'] == '1')  {
		  self::$defaultCombineJSExcludes[] = 'wp-content/themes';
	  }

	  if (!empty(self::$excludesOption['combine_js_exclude_plugins']) &&
	      self::$excludesOption['combine_js_exclude_plugins'] == '1')  {
		  self::$defaultCombineJSExcludes[] = 'wp-content/plugins';
	  }

	  if (!empty(self::$excludesOption['combine_js_exclude_wp']) &&
	      self::$excludesOption['combine_js_exclude_wp'] == '1')  {
		  self::$defaultCombineJSExcludes[] = 'wp-includes';
	  }

    return self::$defaultCombineJSExcludes;
  }

  public function renderBlockingCSSExcludes()
  {
    $excludes = ['wps_inline'];
    $combine_css_excludes = get_option('wpc-excludes');
    $combine_css_excludes = $combine_css_excludes['css_render_blocking'];

    if (is_array($combine_css_excludes)) {
      $excludes = array_merge($excludes, $combine_css_excludes);
    }

    return $excludes;
  }

	public function isAdaptiveExcluded($image_src, $class){

		if ($this->strInArray($image_src, self::$excludesAdaptiveOption)){
			//user exclude for url
			return true;
		}


		if ($this->strInArray($class, self::$defaultAdaptiveExcludes)) {
			//our default exclude for class
			return true;
		}


    if (!empty(self::$excludesAdaptiveOption)) {
      foreach (self::$excludesAdaptiveOption as $exclude) {
        if (strpos($exclude, '#') === 0 && strpos($class, str_replace('#', '', $exclude)) !== false) {
          //user exclude for class
          return true;
        }
      }
    }
		return false;
	}

	public function isWebpExcluded($image_src, $class){

		if ($this->strInArray($image_src, self::$excludesWebpOption)){
			//user exclude for url
			return true;
		}


		if ($this->strInArray($class, self::$defaultWebpExcludes)) {
			//our default exclude for class
			return true;
		}

    if (!empty(self::$excludesWebpOption)) {
      foreach (self::$excludesWebpOption as $exclude) {
        if (strpos($exclude, '#') === 0 && strpos($class, str_replace('#', '', $exclude)) !== false) {
          //user exclude for class
          return true;
        }
      }
    }

		return false;
	}

	public function isLazyExcluded($image_src, $class){

		if ($this->strInArray($image_src, self::$excludesLazyOption)){
			//user exclude for url
			return true;
		}


		if ($this->strInArray($class, self::$defaultLazyExcludes)) {
			//our default exclude for class
			return true;
		}


    if (!empty(self::$excludesLazyOption)) {
      foreach (self::$excludesLazyOption as $exclude) {
        if (strpos($exclude, '#') === 0 && strpos($class, str_replace('#', '', $exclude)) !== false) {
          //user exclude for class
          return true;
        }
      }
    }
		return false;
	}

  public function strInArray($haystack, $needles = [])
  {

    if (empty($needles)) {
      return false;
    }

    $haystack = strtolower($haystack);

    foreach ($needles as $needle) {
      $needle = strtolower(trim($needle));

      if (empty($needle)) continue;

      $res = strpos($haystack, $needle);
      if ($res !== false) {
        return true;
      }
    }

    return false;
  }
}
