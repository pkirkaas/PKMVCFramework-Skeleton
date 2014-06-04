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
/**
 * Base Form Element
 *
 * @author Paul
 */
namespace PKMVC;
/**
 * Builds named HTML elements according to parameters/instance variables.
 * For many input elements, builds generically, but for particular types
 * (textarea, boolean) does special build.
 */
class BaseElement {

  /** An array of valid HTML attributes - if they are included as param keys,
   * will be part of the input element. 'data-XXX' are special cases
   * Let's deal with 'select' & such later....
   */
  protected static $validAttributes = array('label', 'name', 'class', 'id',
          'title', 'style', 'value', 'placeholder', 'step', 'disabled',
      'checked', 'spellcheck', 'rows', 'cols', 'wrap');
 /**
  * Array of validators for this element. These are just for individual elements.
  * The form class will have its own validators for form-wide validation, for
  * example, ensuring two elements are the same,...
  */
  protected $validators = array(); 
  /**
   * In general, the HTML input type, but we extend that for example, with
   * the "boolean" checkbox type
   * @var type 
   */
  protected $type; 

  /**
   * @var array: Assoc. array of attribute names/values
   * Multiple types of arguments can be given to the element.
   * They are parsed, and those that are valid attributes for inclusion in 
   * the input element are added here.
   */
  protected $attributes = array();

  /**
   * @var array: Extra args NOT valid for input attribute values were passed
   * here. Let's keep them around for something later...
   */
  protected $otherAttributes = array();

  /**
   * @var Array: Ass arr of all values required to build this input
   */
  protected $params = array();

  /**
   * The HTML string for the element
   * @var String
   */
  protected $inputStr = '';

  /**
   * Cleans input strings for inclusion as values for HTML attributes
   * within double quotes. Not sure correct for all to go through here...
   * 
   * @param String $val: Possibly dirty string with quotes, etc
   * @return String: Purified input
   */
  public static function clean($val) {
    $val = htmlspecialchars($val,ENT_QUOTES);
    return $val;
  }

  /**
   * Verifies if the named attribute belongs as an attribute in the input
   * element. Aside from just checking if in_array of validAttributes, makes
   * *dispensation for attributes starting with 'data-'.
   * @param String $attr: attribute name
   * @return Boolean: Include in input control?
   */
  public static function isValidAttribute($attr) {
    if (in_array($attr, static::$validAttributes)) {
      return true;
    }
    if (substr($attr,0,5) === 'data-') {
      return true;
    }
    return false;
  }

  /**
   * 
   * @param Array $ags: Assoc array of key/value pairs. Just initializes values
   * See doc for buildHtml() below for details
   */
  public function __construct($args = array()) {
    if (isset($args['type'])) {
      $this->type = $args['type'];
    } else { #default to text
      $this->type = 'text';
      $args['type'] = 'text';
    }
    if (isset($args['inputStr'])) { //We're done...
      $this->inputStr = $args['inputStr'];
      return;
    }
    $this->setValues($args);
  }

  /** Sets values for this element
   * 
   * If key name is valid attribute, adds to attributer array and
   * cleans its value
   * @param array $args: Assoc array of name/value pairs
   * FOR TEXTAREA & BUTTON INPUTS! Must use special val key/name: 'content' to 
   * be inclduded between the open and close tags
   * TODO: Need to do more with different inputs - like button with type submit
   */
  public function setValues($args = array()) {
    foreach ($args as $key =>$val) {
      if (static::isValidAttribute($key)) {
        $this->attributes[$key] = static::clean($val);
      } else { #Leftover args -- save for later?
        #But don't know what they are, so don't clean
        $this->otherAttributes[$key] = $val;
      }
    }
  }

  public function getInputStr() {
    return $this->inputStr;
  }

  /** If $this->inputStr is set, return that, else build the control from the 
   * instance variables
   * @return String: HTML representation of the control
   */
  public function __toString() {


    if ($this->inputStr) {
      return $this->inputStr;
    }
    return $this->buildHtml();
  }

  /** 
   * Builds the HTML element/input represented by the instance data
   * So far, blindly trusting that any "type" passed in is valid
   * @return String: HTML representing control
   */
  public function buildHtml() {
    $specialTypes = array('textarea','boolean','button','subform');
    $atts = $this->attributes; #Include in input
    $type = $this->type;
    //Start building...
    $retstr ='';
    foreach ($atts as $aname => $aval) {
      $retstr .= " $aname=\"$aval\" ";
    }
    if (in_array($type,$specialTypes)) {
      if (($type === 'textarea') || ($type==='button')) { #Special Value...
        $val = '';
        if (isset($this->otherAttributes['content'])) {
          $val = $this->otherAttributes['content'];
        }
        $retstr ="\n<$type $retstr >$val</$type>\n";
      } else if ($type === 'boolean') { #Custum control implemented with two inputs
        $retstr =  static::makeBooleanInput($this->attributes);
      } else if ($type === 'subform') {

      }
    } else { #Not a special type, make a regular input of the type..
      $retstr = "\n<input type='$type' $retstr />";
    }
    return $retstr;
  }

/**
 * ##!! PULLED FROM PKMVCForm!!
 * 
 * Returns a string containing two HTML input elements of the same name -- a hidden
 * input field followed by a check-box. The value of the hidden field will always
 * be set to the emmpty string. If the checkk box is checked, its value will
 * replace the value of the hidden field during a submmit. 
 * @param $name: Either a string representing thte "name" value, or an array of HTML
 * attributes for the elements (includeing 'name' of course!). 
 * 
 * @param $value: The value of the checkox. If empty, just One. The hidden will
 * always be the empty string.
 ^ @ return String: HTML of the paired checkboxes to make a boolean true/false
 */

public static function makeBooleanInput ($name, $checked = false, $value=null) {
  $defaultClass = 'boolean-checkbox';
  $defaultValue = '1';
  if (!is_array($name)) {
    if (!is_string($name) || empty($name)) {
      throw new Exception ("The first argument to to booleanInput() must either be
      a string with the name, or an array with 'name' as a key");
    }
    $name = array('name'=>$name, 'class' =>  $defaultClass,);# 'value'=$value);
  }
  if (!isset($name['name'])) {
    throw new Exception ("Attemmpt to create a Boolean Checkbox control without a name");
  }
  if (!isset($name['class'])) {
    $name['class'] = $defaultClass;
  }
  if (!isset($name['value'])) {
    if (is_null($value)) {
      $value = $defaultValue;
    }
    $name['value'] = $value;
  }
  $checkStr =  " <input type='checkbox' "; 
  $hiddenStr = " <input type='hidden' value='' name='".$name['name'] ."' />";
  if (!isset($name['checked'])) {
    if ($checked) {
      $name['checked'] = 'checked';
    }
  }
  foreach ($name as $key => $val)  {
    $checkStr .= " $key='".htmlspecialchars($val,ENT_QUOTES)."' ";
  }
  $checkStr .= " />";

  $retstr = $hiddenStr .' '.$checkStr;
  return $retstr;
}

  
}

class BaseDbElement extends BaseElement {

}
