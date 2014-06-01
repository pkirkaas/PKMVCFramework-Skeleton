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
/** Renders templates based on Controller data */
namespace PKMVC;
Class ViewRenderer {
  protected static $csss = array();
  protected static $jss = array();

  public $controller;
  public $template; #A string that leads to a file in the form 'controller/view'
  public $data; //Data to render in template
  public static $templateRoot;

  public static function addCss($css) {
    foreach (static::$csss as $cssEl) {
      if ($cssEl == $css) {
        return;
      }
    }
    static::$csss[] = $css;
  }

  public function setCsss($csss = array()) {
    static::$csss = $csss;
    return static::$csss;
  }
  public function getCsss() {
    return static::$csss;
  }

  public static function addJs($js) {
    foreach (static::$jss as $jsEl) {
      if ($jsEl == $js) {
        return;
      }
    }
    static::$jss[] = $js;
    return static::$jss;
  }

  public static function setJss($jss = array()) {
    static::$jss = $jss;
    return static::$jss;
  }
  public static function getJss() {
    return static::$jss;
  }


  /** 
   * Returns a string for all CSS or JS includes, depending on $type.
   * Assumes the given path is either relative to the site root, or absolute
   * with full HTTP & URL.
   * 
   */
  public static function getIncludes($type) {
    if (!in_array($type,array('css','js'))) {
      throw new \Exception("Bad include type: [$type]");
    }
    $retStr = "\n";
    $varArr = $type.'s';
    if (!is_array(static::$$varArr) || empty(static::$$varArr)) {
      return false;
    }
    foreach (static::$$varArr as $item) {
      $pref = substr($item,4);
      if ($pref !== 'http') {
        $p = $item[0];
        if ($p != '/') {
          $item = '/'.$item;
        }
        $item = getBaseUrl().$item;
      }
      if ($type === 'css') {
        $retStr .= "<link type='text/css' rel='stylesheet' href='$item' />\n";
      } else {
        $retStr .= "<script language='javascript' src='$item'></script>\n";
      }
    }
    static::$$varArr = array();
    return $retStr;
  }

  public static function getCss() {
    return static::getIncludes('css');
  }
  public static function getJs() {
    return static::getIncludes('js');
  }

  /** Makes a menu based on the $menuArray -- see docs for format.
   * By default, based on a "bootstrap" based layout, but can be changed with
   * template
   * 
   * @param Array $menuArray
   * @param String $menuTemplate: Name of menu template without .phtml suffix
   * @return String -- Menu for bootstrap
   */
  public static function makeMenu($menuArray, $menuTemplate = null) {
    if (!$menuTemplate) {
      $menuTemplate = "default-menu";
    }
    $templatePath = static::$templateRoot.'/'.$menuTemplate.'.phtml';
    if (!file_exists($templatePath)) {
      throw new \Exception("Building menu; template [$templatePath] not found");
    }
    

  }

  public function __construct(BaseController $controller = null) {
    $this->controller = $controller;
    $this->templateRoot = BASE_DIR . '/templates';
  }

  public function setTemplate($template) {
    $this->template = $template;
  }

  public function getTemplate() {
    return $this->template;
  }

  public function setData($data) {
    $this->data = $data;
  }

  public function getData() {
    return $this->data;
  }

  public function render($data=array(),$template = null) {
    if (empty($template)) {
      if (empty($data)) { 
        return '';
      }
      if ( is_string($data) ) {
        return $data;
      } else {
        throw new \Exception("No template in this ViewRenderer [" . class_name($this) . "]");
      }
    }
    $fpath = $this->getFileFromTemplateName($template);
    if (!file_exists($fpath)) {
      throw new \Exception("Template file [$fpath] not found");
    }
    if (is_array($data)) extract($data);
    ob_start();
    include ($fpath);
    $out = ob_get_contents();
    ob_end_clean();
    return $out;
  }

  public function getFileFromTemplateName($templateName) {
    $root = $this->templateRoot;
    $fpath = $root . "/$templateName" . '.phtml';
    if (!file_exists($fpath)) {
      throw new \Exception("Loading template [$templateName], File [$fpath] not found");
    }
    return $fpath;
  }

  #static methods and members
}

class RenderResult {

  public $result;
  public $template;
  public $viewRenderer;

  public function __construct($result = null, $template = null) {
    $this->result = $result;
    $this->template = $template;
  }

  public function __toString() {
    try {
    if (!$this->viewRenderer instanceOf ViewRenderer) {
      $this->viewRenderer = new ViewRenderer();
      //return $this->viewRenderer($this->result, $this->template);
      return $this->viewRenderer->render($this->result, $this->template);
    }
    } catch (\Exception $e) {
      pkdebug("Exception:", $e);
      $stack = \pkstack_base();
      $exceptionMsg = "Exception: ".$e->getMessage();
      return "<pre>".$exceptionMsg . "\n" . $stack . "</pre>";
    }
  }

}

/** Make an array of partials to pass to the view */
class PartialSet extends \ArrayObject {
  public $separator = ' ';
  public function __construct($separator = '') {
    $this->separator = $separator;
  }
  public function __toString () {
    $str = ' ';
    foreach ($this as $item) {
      $str.= ' '.$item.$this->separator;
    }
    return $str;
  }
}

  
