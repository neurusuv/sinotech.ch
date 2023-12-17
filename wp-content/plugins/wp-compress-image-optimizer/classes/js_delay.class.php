<?php


class wps_ic_js_delay
{

  public static $excludes;
  public static $footerScripts;

  public function __construct()
  {
    self::$excludes = new wps_ic_excludes();
  }


  public function printFooterScripts()
  {
    $html = '';

    if (!empty(self::$footerScripts)) {
      foreach (self::$footerScripts as $script) {
        $html .= $script[0];
      }
    }

    return $html . '</body>';
  }

  public function scriptsToFooter($tag)
  {
    $original_tag = $tag;
    if (is_array($tag)) {
      $tag = $tag[0];
    }
    if (is_array($tag)) {
      $tag = $tag[0];
    }
    if (self::$excludes->strInArray($tag, self::$excludes->scriptsToFooterExcludes())) {
      return $tag;
    }
    self::$footerScripts[] = $original_tag;
    return '';
  }


  public function delay_script_replace($tag)
  {

    if (!empty($_GET['removeScripts'])) {
      return '';
    }

    if (!empty($_GET['dbgDelay_replace'])) {
      return print_r(array($tag),true);
    }

    if (is_array($tag)) {
      $tag = $tag[0];
    }

    $tagLower = strtolower($tag);

    /**
     * Do not delay Inlined Javascript
     */
    if (strpos($tagLower, 'wps-inline') !== false) {
      return $tag;
    }

    // Default delay exclude patterns
    $pattern1 = '/jquery-?[0-9.](.*)(.min|.slim|.slim.min)?.js/';
    if (preg_match($pattern1, $tag, $matches) && strpos($tagLower, 'migrate') === false && strpos($tagLower, 'slider') === false && strpos($tagLower, 'sticky') === false && strpos($tagLower, 'ui') === false && strpos($tagLower, 'jqueryParams') === false) {
      return $tag;
    }

    if (strpos($tagLower, 'optimizer.adaptive') !== false) {
      return $tag;
    }

    if (strpos($tagLower, 'wpcompress-aio-js-extra') !== false) {
      return $tag;
    }

    /**
     * Get the src and remove the <script> element
     */
    preg_match('/src=(["\'])(.*?)\1/is', $tag, $matches);

    #return print_r(array($matches),true);

    // No matches found, it's inline... is it excluded?
    if (!$matches || empty($matches[2])) {
      // It's inline, is it excluded?
      $delayJSExcludes = self::$excludes->delayJSExcludes();

      if (!empty($_GET['dbgDelay_replace_excludes'])) {
        return print_r(array($delayJSExcludes),true);
      }

      if (!empty($_GET['dbgDelay_replace_excludes_match'])) {
        return print_r(array($tag, self::$excludes->strInArray($tag, $delayJSExcludes)),true);
      }
      
      if (self::$excludes->strInArray($tag, $delayJSExcludes)) {
        return $tag;
      }
    } else {

      // It's javascript file
      $tag = str_replace("'", '"', $tag);

      $delayJSExcludes = self::$excludes->delayJSExcludes();

      if (!empty($_GET['dbgDelay_replace_excludes_match_src'])) {
        return print_r(array($tag, self::$excludes->strInArray($tag, $delayJSExcludes)),true);
      }

      if (self::$excludes->strInArray($tag, $delayJSExcludes)) {
        return $tag;
      }
    }

	  if (strpos(strtolower($tag), 'type=') === false) {
		  $tag = str_replace('<script', '<script type="wpc-delay-script"', $tag);
	  } else {
		  $tag = str_replace('type="text/javascript"', 'type="wpc-delay-script"', $tag);
		  $tag = str_replace("type='text/javascript'", "type='wpc-delay-script'", $tag);
	  }

    return $tag;
  }


}