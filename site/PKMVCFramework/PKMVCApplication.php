<?php
###
/**
 * PKMVC Framework 
 *
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @link     
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */
namespace PKMVC;
/** Application Base Path */
/** Root of everything 
 */
Class ApplicationBase {
  public static $renderArr = array();
  public static $controllers = array();
  public static $depth = 0;
  //public static function exec($controller=null, $action=null, $args=null, $arg2=null, $arg3=null, $arg4=null) {
  //Can take extra arguments
  public static function exec(/*$controller, $action, $argN...*/) {
    $args = func_get_args();
    $controller = array_shift($args);
    $action = array_shift($args);

    if (!$controller) $controller = 'index';
    if (!$action) $action = 'index';
    $controllerName = $controller.BaseController::CONTROLLER;
    if (!class_exists($controllerName)) {
      throw new \Exception("Controller [$controllerName] Not Found");
    }
    $actionName = $action.BaseController::ACTION;
    $partialName = $action.BaseController::PARTIAL;
    if (method_exists($controllerName,$actionName)) {
      $methodName = $actionName;
    } else if (method_exists($controllerName,$partialName)) {
      $methodName = $partialName;
    } else {
      throw new \Exception ("Partial or Action [$action] " .
       "for Controller [$controllerName] not found");
    }
    //$controller = new ControllerWrapper(new $controllerName());
    $controller =  $controllerName::get();
    $result = call_user_func_array(array($controller,$methodName), $args);
    $template = $controller->getTemplate();
    $newResult = new RenderResult($result,$template);
    return $newResult;
  }
  
  public static function layout($controller=null, $action=null, $args=null) {
    $wrapper = LayoutController::get();
    $result = $wrapper->layoutAction($controller, $action, $args);
    $template = $wrapper->getLayout();
    $newResult = new RenderResult($result,$template);
    return $newResult;
  }

  /**
   * Sets a session value. Can be $key string with key value pair,
   * or an array with multiple key/value pairs. Serializes the value
   * @param Array|String $key
   * @param String $val
   */
  public static function setSessionVal($key, $val = null) {
    $setArr = array();
    if (is_array($key)) {
      $setArr = $key;
    } else if (is_string($key)) {
      $setArr[$key] = $val;
    } else { #Bad input
      throw new \Exception("Bad input to set sessin value");
    }
    foreach ($setArr as $skey => $sval) {
      $_SESSION[$skey] = serialize($sval);
    }
  }

  /** Returns the unserialzed SESSION value for $key
   * 
   * @param string $key
   * @return mixed -- restored/unserialized val
   */
  public static function getSessionVal($key) {
    if (isset ($_SESSION[$key])) {
      return unserialize($_SESSION[$key]);
    }
    return null;
  }
}

/**
 * The initializing object
 */
Class Application extends ApplicationBase {
  public $controller;
  public $action;
  public function __construct(Array $args = null) {
  }

  public function run( $action = null, $controller = null, $args=null) {
    #TODO: Lame attempt at session security. Redo
    session_start();
    session_regenerate_id();

    $results = ApplicationBase::layout($controller, $action, $args);
    echo $results;
  }
}
