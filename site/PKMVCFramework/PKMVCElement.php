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

  /** An array of valid HTML input attributes - if they are included as param keys,
   * will be part of the input element. 'data-XXX' are special cases
   * Let's deal with 'select' & such later....
   * 
   * If 'input' = 'subform', 'content' should be the 'subform' object instance
   * or String HTML
   * 
   * If 'input' = 'html', 'content' should be an HTML string and just be
   * echoed. This is for the convenience of including HTML in your 
   * form if you don't want to build a template. Will be echoed in the order
   * added.
   */
  protected static $validAttributes = array(
      'accesskey', 'class', 'contenteditable', 'contextmenu', 'dir',
      'draggable', 'dropzone', 'hidden', 'id', 'lang', 'spellcheck',
      'style', 'tabindex', 'title', 'translate', 'label', 'name',
      'checked', 'accept', 'alt', 'autocomplete', 'autofocus',
      'form', 'formaction', 'formenctype', 'formmethod', 'formnovalidate',
      'formtarget', 'height', 'list', 'max', 'maxlength', 'min', 'multiple',
      'step', 'type', 'value', 'width', 'for'
  );

  /**
   *
   * @var array: Valid form element types (Added my own: boolean, HTML, subform)
   */
  protected static $validInputs = array(
      'input', 'textarea', 'button', 'select', 'option', 'optgroup',
      'fieldset', 'label', 'boolean', 'html', 'subform',
  );

  /**
   * @var array: If element instance of "input", what are valid input types? 
   */
  protected static $validTypes = array(
      'checkbox', 'color', 'date', 'datetime', 'datetime-local', 'email', 'file',
      'hidden', 'image', 'month', 'number', 'password', 'radio', 'range', 'reset',
      'search', 'submit', 'tel', 'text', 'time', 'url', 'week', 'button',
  );

  /**
   *
   * @var array: elements that are not self-closing, need a closing tag, and
   * assume that the special attribute 'content' is set and will be echoed
   * between the open/close tags. 'select' should also be a content input,
   * but we treat it special
   */
  protected static $contentInputs = array('textarea', 'button', 'label',
  );

  /**
   * @var array: Element names/types treated specially when rendered
   */
  protected static $specialInputs = array('boolean',
      'select', 'subform', 'html', 'label');

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
  protected $type = false; #default
  protected $input = false; #Default. Could be button, etc 

  /**
   * @var array: Assoc. array of attribute names/values
   * Multiple types of arguments can be given to the element.
   * They are parsed, and those that are valid attributes for inclusion in 
   * the input element are added here.
   */
  protected $attributes = array();

  /**
   * @var array: Extra args NOT valid for input attribute values were passed
   * here. Fx, 'content'. Let's keep them around for something later.
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
    $val = htmlspecialchars($val, ENT_QUOTES);
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
    return MVCLib::isValidAttribute($attr, static::$validAttributes);
  }

  /**
   * 
   * @param Array $ags: Assoc array of key/value pairs. Just initializes values
   * See doc for buildHtml() below for details
   */
  public function __construct($args = array()) {

    #set some defaults, then call set Values with rest
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
    if (isset($args['inputStr'])) { //We're done...
      $this->inputStr = $args['inputStr'];
      return;
    }
    if (!array_key_exists('input', $args)) {#No key set, so use default
      $this->input = 'input';
    } else { #allows creator to spefically NOT have an input by setting null 
      $this->input = $args['input'];
    }
    if (!array_key_exists('type', $args) && ($this->input == 'input')) {
      #No key set, input is 'input', so use default
      $this->type = 'text';
    } else if (isset($args['type'])) {
      $this->type = $args['type'];
    }
    foreach ($args as $key => $val) {
      if (($key == 'input') || ($key == 'type')) {
        continue;
      } else if (static::isValidAttribute($key)) {
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
    //$specialInputs = array('textarea','boolean','button','select','subform');

    $atts = $this->attributes; #Include as elemement attributes
    $type = $this->type;
    $input = $this->input; #Maybe button?
    //Start building...
    $retstr = '';
    foreach ($atts as $aname => $aval) {
      $retstr .= " $aname=\"$aval\" ";
    }
    if (in_array($input, static::$contentInputs)) {
      $val = '';
      if (isset($this->otherAttributes['content'])) {
        $val = $this->otherAttributes['content'];
      }
      $retstr = "\n<$input $retstr >$val</$input>\n";
    } else if ($input === 'boolean') { #Custum control implemented with two inputs
      $retstr = static::makeBooleanInput($this->attributes);
    } else if ($input === 'subform') { #Just echo the subform...
      $val = '';
      if (isset($this->otherAttributes['content'])) {
        $val = $this->otherAttributes['content'];
      }
      #Let's have fun and implement some more functionality. If 'type' is set
      #and == 'fieldset', wrap the subform in the fieldset tag, and apply all
      #the attributes, like 'class', etc...
      if ($type == 'fieldset') {
        $ret = new PartialSet();
        $ret[] = "\n<fieldset $retstr >\n";
        $ret[] = $val;
        $ret[] = "\n</fieldset>\n";
        return $ret;
      }
      return $val;
    } else if ($input === 'select') { #Build the select...
    } else if ($input === 'html') { #Convenience, just echo without question...
      $retstr = $val;
    } else { #Not a special type, make a regular input of the type..
      $retstr = "\n<'$input' type='$type' $retstr />";
    }
    $retstr .= "\n";
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
  public static function makeBooleanInput($name, $checked = false, $value = null) {
    $defaultClass = 'boolean-checkbox';
    $defaultValue = '1';
    if (!is_array($name)) {
      if (!is_string($name) || empty($name)) {
        throw new Exception("The first argument to to booleanInput() must either be
      a string with the name, or an array with 'name' as a key");
      }
      $name = array('name' => $name, 'class' => $defaultClass,); # 'value'=$value);
    }
    if (!isset($name['name'])) {
      throw new Exception("Attemmpt to create a Boolean Checkbox control without a name");
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
    $checkStr = " <input type='checkbox' ";
    $hiddenStr = " <input type='hidden' value='' name='" . $name['name'] . "' />";
    if (!isset($name['checked'])) {
      if ($checked) {
        $name['checked'] = 'checked';
      }
    }
    foreach ($name as $key => $val) {
      $checkStr .= " $key='" . htmlspecialchars($val, ENT_QUOTES) . "' ";
    }
    $checkStr .= " />";

    $retstr = $hiddenStr . ' ' . $checkStr;
    return $retstr;
  }

  /**
   * Creates a select box with the input
   * @param array $args: Assoc array of input args:
   * @key name - String - The HTML Control Name. Makes class from 'class-$name'
   * @key label - String - Opt - The label on the control
   * @key key_str - The key name of the Value select option array element
   * @key val_str - The key name for the array element to display in the option
   * @key data - Array - The array of key/value pairs
   * @key selected - Opt - String or Null - if present, the selected value
   * @key none - String or Null - if present, the label to show for a new
   *   entry (value 0), or if null, only allows pre-existing options
   * @return String -- The HTML Select Box
   * 
   * TODO: Add support for optiongroups?
   * */
  #public function makePicker($name, $key, $val, $arr, $selected = null, $none = null) {
  public function makePicker($args) { #$name, $key, $val, $arr, $selected = null, $none = null) {
    $reqOpts = array('name','key_name', 'val_name', 'data',);
    $opts = array_keys($args);
    if (array_diff($reqOpts, $opts)) { #Not all req opts present
      $optStr = implode(',',$opts);
      $reqOptStr = implode(',',$reqOpts); 
      throw new \Exception("Required Options: [$reqOptStr], got: [$optStr] ");
    }
    #We have all required options, set extra attributes
    $attStr = '';
    foreach ($args as $akey => $aval) {

    }
    if (!isset($args['class'])) {
      $args['class'] = "$name-sel";
    }

    $select = "<select name='$name' class='$name-sel'>\n";
    if ($none) $select .= "\n  <option value=''><b>$none</b></option>\n";
    foreach ($data as $row) {
      $selstr = '';
      if ($selected == $row[$key_str]) $selstr = " selected='selected' ";
      $option = "\n  <option value='" . $row[$key_str] . "' $selstr>"
        . $row[$val_str] . "</option>\n";
      $select .= $option;
    }
    $select .= "\n</select>";
    return $select;
  }

}

class BaseDbElement extends BaseElement {
  
}
