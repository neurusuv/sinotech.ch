<?php


class wps_minifyHtml
{
  
  public function __construct() {
  }
  
  
  public function minify($buffer)
  {
    $search = [
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
    ];
    
    $replace = [
        '>',
        '<',
        //'\\1',
        ''
    ];
    
    $buffer = preg_replace($search, $replace, $buffer);
    
    return $buffer;
  }

  
  
}