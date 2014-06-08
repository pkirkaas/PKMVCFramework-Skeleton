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
 * For inclusion of input elements in Forms. Implements __toString to output
 * appropriate HTML.
 * 
 * Builds named HTML elements according to parameters/instance variables.
 * For many input elements, builds generically, but for particular types
 * (textarea, boolean) does special build.
 * 
 * Rules for building a control/element: Build an element by giving an 
 * associative array of values in the constructor, or calling
 * "$el->setValues($assocArray);" after the empty element is created.
 * The args for value are an associative array of $key/value pairs, where the
 * keys can fall in several groups:
 * 
 * KeyGroup 1: from (input, type, label, ) 
 *   where the key name is the name of a member of the BaseElement class and
 *   the value is assigned to that member. Like, ->input can be 'input',
 *   'textarea', etc.
 *   If 'label' is set, the element is wrapped in a div with two class names-
 *   "
 * KeyGroup 2: The key name is among normal valid form element attribute value
 *   names, like 'value', 'step'(for number controls), 'class', etc, in which 
 *   case the key is added to the $attributes array as a key, with the value 
 *   html "cleaned" and added as the value, as "$key"=>static::clean($value)"
 * 
 * KeyGroup 3: Special Attributes: Attributes that have a special meaning
 *   for this class to help build the control. Like 'content' - if the input
 *   is a textarea or button, so the output will be "<button>$content</button>
 *   These are added to "$this->specialAttributes" array as: "$key=>$value"
 * 
 * Some special considerations:
 * 
 */


class BaseElement {

  /** An array of valid HTML input attributes - if they are included as param
   * keys, will be part of the input element.
   * Should not be accessed directly, accessed instead by the function
   * "static::isValidAttribute($attrName);" to account for the special cases of
   * 'data-XXX' attributes. isValidAttribute also checks a separate list of
   * HTML Global attributes which are not specific to form elements and not
   * in this array.
   */
  protected static $validAttributes = array(
      'accesskey', 'class', 'contenteditable', 'contextmenu', 'dir',
      'draggable', 'dropzone', 'hidden', 'id', 'lang', 'spellcheck',
      'style', 'tabindex', 'title', 'translate',  'name',
      'checked', 'accept', 'alt', 'autocomplete', 'autofocus',
      'form', 'formaction', 'formenctype', 'formmethod', 'formnovalidate',
      'formtarget', 'height', 'list', 'max', 'maxlength', 'min', 'multiple',
      'step', 'type', 'value', 'width',
  );

  protected $ctl_pair_class="ctl-pair";

  /**
   * @var array: Valid form element types (Added my own: boolean, HTML, subform)
   * 
   * If 'input' = 'subform', 'content' should be the 'subform' object instance
   * or String HTML
   * 
   * If 'input' = 'html', 'content' should be an HTML string and just be
   * echoed. This is for the convenience of including HTML in your 
   * form if you don't want to build a template. Will be echoed in the order
   * added. If don't want to bother making a form template
   * (see BaseForm documentation)
   */
  protected static $validInputs = array(
      'input', 'textarea', 'button', 'select', 'option', 'optgroup',
      'fieldset', 'boolean', 'html', 'subform',
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
      'select', 'subform', 'html',);

  /**
   *
   * @var array: Names of key attributes from the initialization array that
   * direct members of this class, and assigned directly. 
   */
  protected static $memberAttributes = array(
      'label', 'for', 'ctl_pair_class', 'input', 'type'
      );
  protected static $specialAttributes = array('label', 'for', 'ctl_pair_class');

  /**
   * Array of validators for this element. These are just for individual elements.
   * The form class will have its own validators for form-wide validation, for
   * example, ensuring two elements are the same,...
   */
  protected $validators = array();
  protected $label = '';
  protected $for = '';

  /**
   * In general, the HTML input type, but we extend that for example, with
   * the "boolean" checkbox type
   * @var type 
   */
  protected $type = false; #default
  protected $input = 'input'; #Default. Could be button, etc 

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

  public function setMemberAttributes($args) {
    foreach ($args as $key => $value) {
      if (in_array($key, static::$memberAttributes)) {
        $this->$key = static::clean($value);
      }
    }
  }

  /**
   * Returns the value of any attribute, normal or special, or false if none
   * @param type $attrName
   * @return boolean|value: The attribute value if it exists, else boolean false
   * @throws \Exception: if $attrName not a string
   */
  public function getAttribute($attrName) {
    if (!attrName || !is_string($attrName)) { #bad call
      throw new \Exception("Improper arg for attrName");
    }
    $attributes = $this->getAllAttributes();
    if (!array_key_exists($attrName,$attributes)) {
      return false;
    }
    return $attributes[$attrName];
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
    $this->setMemberAttributes($args);
    /*
    if (isset($args['ctl_pair_class'])) {
      $this->ctl_pair_class = $args['ctl_pair_class'];
    }
    if (array_key_exists('label', $args)) {#No key set, so use default
      $this->label = static::clean($args['label']);
    } 
    if (array_key_exists('for', $args)) {#No key set, so use default
      $this->for = static::clean($args['for']);
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
     * 
     */
    if (($this->input == 'input') && !$this->type) {
      $this->type = 'text';
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
    $ret = new PartialSet();
    //$specialInputs = array('textarea','boolean','button','select','subform');
    #if $this->label set, create a label, and a div ctl pair to hold label and 
    #ctl...
    $labelCtl='';
    if ($this->label) {
      $val = $this->label;
      $for = $this->for ? " for = '{$this->for}' " : '';
      $el_class = $this->getAttribute('class');
      $ctlPairClass = $this->ctl_pair_class; #Can be customized in create
      $labelCtl = "<div class='$ctlPairClass $el_class-pair' >"
        . " <label $for>$val</label> ";
      $ret[] = $labelCtl;
    }
    $type = $this->type;
    $input = $this->input; #Maybe button?
    //Start building...
    $attrStr = $this->makeAttrStr();

    if (in_array($input, static::$contentInputs)) {#Add content and close tag
      $val = '';
      if (isset($this->otherAttributes['content'])) {
        $val = $this->otherAttributes['content'];
      }
      $ret[] = "\n<$input $attrStr >$val</$input>\n";
    } else if ($input === 'boolean') { #Custum control implemented with two inputs
      $ret[] = static::makeBooleanInput($this->attributes);
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
        $ret[] = "\n<fieldset $attrStr >\n";
        $ret[] = $val;
        $ret[] = "\n</fieldset>\n";
      } else {
        $ret[]= $val;
      }
    } else if ($input === 'select') { #Build the select...
      $ret[] = $this->makePicker();
    } else if ($input === 'html') { #Convenience, just echo without question...
      $ret[] = $val;
    } else { #Not a special type, make a regular input of the type..
      $ret[] = "\n<'$input' type='$type' $attrStr />";
    }
    if ($labelCtl) {
      $ret[] = "\n</div>\n";
    } else {
      $ret[] = "\n";
    }
    return $ret;
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

  public function getAttributes() {
    return $this->attributes;
  }

  public function getOtherAttributes() {
    return $this->otherAttributes;
  }

  public function getAllAttributes() {
    return array_merge ($this->getAttributes(), $this->getOtherAttributes());
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
  public function makePicker() { #$name, $key, $val, $arr, $selected = null, $none = null) {
    $inputAttributes = $this->getAttributes();
    $otherAttributes = $this->getOtherAttributes();
    $allAttributes = $this->getAllAttributes();
    $optNames = array_keys($allAttributes);
    $reqOpts = array('key_name', 'val_name', 'data',);
    if (array_diff($reqOpts, $optNames)) { #Not all req opts present
      $optStr = implode(',',$optNames);
      $reqOptStr = implode(',',$reqOpts); 
      throw new \Exception("Required Options: [$reqOptStr], got: [$optStr] ");
    }
    #We have all required options, set extra attributes
    $name = $this->name;
    if (!isset($inputAttributes['class'])) {
      $this->attributes['class'] = "$name-sel";
    }
    $attStr = $this->makeAttrStr();

    $none = isset($otherAttributes['none'])?$otherAttributes['none']:false;
    $selected = isset($otherAttributes['selected'])?$otherAttributes['selected']:false;
    $select = "<select $attStr >\n";
    $data = $otherAttributes['data'];
    $key_str = $otherAttributes['key_str'];
    $val_str = $otherAttributes['val_str'];
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

  /**
   * Makes an inner attribute string from regular HTML form element attributes
   * @return String: HTML attribute string for inclusion in element/control,
   * like: " name='aname' class='aclass' ... "
   */
  public function makeAttrStr() {
    $attrStr = " name='{$this->name}' ";
    $attributes = $this->getAttributes();
    foreach ($attributes as $key =>$value) {
      $attrStr .= " $key='$value' ";
    }
    return $attrStr;
  }

}

class BaseDbElement extends BaseElement {
  
}
