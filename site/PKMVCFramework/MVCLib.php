<?php
namespace PKMVC;
/**
 * PKMVC Framework 
 *
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @link     
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */
/**
 * Description of PKMVCLib
 *
 * @author Paul Kirkaas
 */
class MVCLib {
  /** Returns false if false, else the string with end removed */
  public static function endsWith($str,$test) {
    if (! (substr( $str, -strlen( $test ) ) == $test) ) {
      return false;
    }
    return substr($str, 0, strlen($str) - strlen($test));
  }
  
  //Returns the base action/partial name with Action/Parial removed
  public static function getMethodBase($methodName) {
    
  BaseController::PARTIAL;
  BaseController::ACTION;
}
  /** Gets the string string "controller/method" for tempaltes */
  public static function getDefaultTemplate($controllerName, $methodName) {


  }
  public static $globalHtmlAttributes= array (
      'accesskey', 'class', 'contenteditable', 'contextmenu', 'dir',
      'draggable', 'dropzone', 'hidden', 'id', 'lang', 'spellcheck',
     'style', 'tabindex', 'title', 'translate',
  );

  /**
   * Merges all input arrays, with each other and with
   * static::$globalHtmlAttributess, adds a special check for "data-*"
   * attributes, and returns true or false
   * @param String $attr: Attribute Name
   * @param Mixed $OptArgs: Any number of arguments, which can be strings
   * or indexed arrays of strings, or indexed arrays of indexex arrays
   * of strings...
   * 
   * @return boolean: Is the attribute acceptable in the context?
   */
  public static function isValidAttribute($attr /*Array|String $arg1, ...*/) {
    $args = func_get_args();
    array_shift($args);
    $validAttributes = array_flatten($args,static::$globalHtmlAttributes);
    if ( in_array($attr, $validAttributes)) {
      return true;
    }
    if (substr($attr,0,5) === 'data-') {
      return true;
    }
    return false;
  }
}