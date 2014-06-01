<?php 
/**
 * PKMVC Framework 
 *
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @link     
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */
/** The Base Controller & Wrapper
 *  Paul Kirkaas
 */
namespace PKMVC;

/**
 * Wraps the base controller (& descendants), intercepts all actions so other 
 * methods can be called before and after.
 */
Class ControllerWrapper {
  protected $controller;
  public function __construct($controller = null, $args = null) {
    if ($controller instanceOf BaseController) {
      $this->setController($controller);
    } else if(is_string($controller) && class_exists($controller.'Controller')) {
      $controllerName = $controller."Controller";
      $this->setController(new $controllerName($args));
    }
  }
  

  public function getController() {
    return $this->controller;
  }

  public function setController(BaseController $controller) {

    $controller -> wrapper = $this;
    $this->controller = $controller;
  }

  public function __call($methodName, Array $argArr = array()) {
    if (empty($this->controller)) { 
      throw new Exception('No controller');
    }
    if (!is_callable(array($this->controller,$methodName))) {
      throw new Exception(get_class($this->controller).
      " doesn't implement $methodName");
    }


    $controllerName = get_class($this->controller);
    $controllerRoot =  MVCLib::endsWith($controllerName,BaseController::CONTROLLER);
    $act = '';
    $resarr=array();
    if ($base = MVCLib::endsWith($methodName,BaseController::ACTION)) {
        $methodRoot = $base;
        $act = $resarr['act'] = BaseController::ACTION;
    }
    if ($base = MVCLib::endsWith($methodName, BaseController::PARTIAL)) {
        $methodRoot = $base;
        $act = $resarr['act'] = BaseController::PARTIAL;
    }
    //$argArr['method']=$methodName;

    if ($act) {
      $this->controller->setTemplate("$controllerRoot/$methodRoot");
      $resarr['act'] = $act;
    }
    $resarr['preExecute'] =
        call_user_func_array(array($this->controller,'preExecute'),$argArr);
    //$argArr['preExecute'] = $resarr['preExecute'];
    //$resarr['actionResult']=call_user_func_array(array($this->controller,$methodName),$argArr);
    $resArr['actionResult']=call_user_func_array(array($this->controller,$methodName),$argArr);

    $argArr['actionResult'] = $resArr['actionResult'];
    $resArr['postExecute']=call_user_func_array(array($this->controller,'postExecute'),$argArr);
    return $resArr['actionResult'];

    #If method ends in Action, do the pre/post execute, else just execute & return
    return call_user_func_array(array($this->controller,$methodName),$argArr);
  }
}

class BaseController {
  const ACTION = "Action";
  const PARTIAL = 'Partial';
  const CONTROLLER = 'Controller';
  public $wrapper; #The wrapper object
  public $viewRenderer;
  public $templates = array();
  public $template;
  public static $layout = 'layout';
  /**
   *
   * @var Array of keyed arrays containing HTML -- filled in by anyone who wants
   */
  public static $slots = array();

  /** Controllers normally only add content to their view templates. But by
   * filling "slots", anyone can insert arbitrary HTML, which can be retrieved
   * in any view. For example, a menu that appears above the controller/action
   * view, but should be created by the controller.
   * 
   * @param array $keys: An indexed array of key names, to arbitrary depth, used
   * to index the array of slots. Like, array('controllername', 'submenu'). But
   * typically, only like "array('menu');"
  
   * @param String $val: The HTML string to put in the slot
   */
  public static function fillSlot(Array $keys, $val = null) {
    $subarr = &static::$slots;
    #Recursive function to fill the array to appropriate depth..
    static::fillArr($subarr,$keys,$val);
  }

  /** General array utility to traverse down an array of keys to end, then
   * set value.
   * @param array $fillArr
   * @param array $keys
   * @param type $val
   */
  public static function fillArray(Array &$fillArr, Array $keys, $val=null) {
    if (empty($keys) || !sizeof($keys)) { #Shouldn't be here
      throw new \Exception("Empty Keys array");
    }
    $key = array_shift($keys);
    if (empty($keys) || !sizeOf($keys)) { #Hit bottom
      $fillArr[$key] = $val;
      return;
    }
    if (!isset($fillArr[$key])) {
      $fillArr[$key] = array();
    }
    static::fillArray($fillArr[$key], $keys, $val);
  }

  /** Retrieves the value at the end of the key chain. If not
   * set, returns null. Do same here as set -- call recursive function...
   * @param array $keys: Sequential indexed array of key names
   */
  public static function getSlot(Array $keys) {
    $slotArr = static::$slots;
    $val = static::getArrayDepth($slotArr, $keys);
    return $val;
  }

  public static function getArrayDepth($slotArr, $keys) {
    if (empty($keys) || !sizeof($keys)) { #Shouldn't be here
      throw new \Exception("Empty Keys array");
    }
    $key = array_shift($keys);
    if (!isset($fillArr[$key])) { //Not set, done, return null;
      return null;
    }
    if (empty($keys) || !sizeOf($keys)) { #Hit bottom
      if (isset($fillArr[$key])) { 
        return $fillArr[$key];
      } else {
        return null;
      }
    } #keep trying...
    return static::getArrayDepth($slotArr[$key],$keys);
  }

  public static function getLayout() {
    return static::$layout;
  }
  #Only get through get - to return the wrapper
  public static function get($args=null){
    $class = get_called_class();
    $controller = new $class($args);
    $wrapper = new ControllerWrapper($controller);
    return $wrapper;
  }

  public static function getControllerName() {
    $cclass = get_called_class();
    return substr($cclass,-1*strlen('Controller'));
  }
  public function setTemplate($template) {
    $this->template = $template;
  }
  public function getTemplate() {
    return $this->template;
  }

  /** Does nothing in base controller, but any controller can define this
   * method, which will get called before each action in that controller
   * @param type $args
   */
  public function preExecute($args = null) {
  }

  /** Does nothing in base controller, but any controller can define this
   * method, which will get called after each action in that controller
   * @param type $args
   */
  public function postExecute($args = null) {
  }



  //Make sure only get through "get" -- to return wrapper
  protected function __construct($args=null) {
  }

  public function getActionName() {
  }

}


class LayoutController extends BaseController {
  protected $components = array();
  public function __construct($args = null) {
    parent::__construct($args);
    $this->setParams($args);
  }

  public function setParams($args = null) {
    if (!$args || !sizeof($args)) {
      return;
    }
    foreach ($args as $key=>$val) { #Check keys and do something...
      
    }
  }

  /** Specifies additional components to be included in the layout
   * -- like menus..
   * @param Array $component: one or more associative compound array of:
   * array('componentName'=>array('controllerName', 'actionName', $args);
   */
  public function setComponent(Array $component) {
    foreach ($component as $key => $compound) {
      $this->components[$key] = $compound;
    }
  }



  public function layoutAction($controller,$action,$args) {
    #return array("content" => "Such a shortcut!");
    $data = array();
    $data['content'] = ApplicationBase::exec($controller,$action,$args);
    $components = $this->components;
    #So absolutely NOT the way I want to leave this -- quick & dirty for now
    foreach ($component as $key => $compound) {
      $data[$key] = ApplicationBase::exec(array_shift($compound),
                                          array_shift($compound),
                                          array_shift($compound));
    }
    return $data;

  }
   
}

