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
#TODO: Rename all references to class/instance attributes/members to: properties
#like, $instanceProperties. Or $instancePropertyNames....
namespace PKMVC;

/**
 * For everything that can be included in a form -- including a form itself,
 * form elements/inputs, subforms, and FormSets/subform collections
 */
interface ElementInterface {
  public function __toString();
}

/**
 * The base class for forms, subforms, and form elements (controls)
 * Naming Overload: All the angle-bracket enclosed stuff in HTML are
 * Elements - Here we use "Element" to mean "Submit" elements -- that is,
 * forms, inputs, etc. Furthermore, the "Form" class includes the "subform"
 * concept, which is just a collection of input elements, without an enclosing
 * "form" tag. The BaseFormComponent is the mother to them all....
 * 
 * Arguments/Settings:
 * The components have names, which are set in the $_POST array. We try to 
 * automate/default as much as possible, while allowing for overriding/customizing
 * defaults. 
 * 
 * The default approach for providing names to the elements is to provide a
 * 'name_segment' string - not a full array/sequence. The name_segment will be
 * added to prior name_segment components. This can be overridden by instead
 * passing an explicit 'name' parameter, which will be used instead.
 */
abstract class BaseFormComponent extends PKMVCBase implements ElementInterface {
  const TPL_STR = '__TEMPLATE__';
  
  protected static $validAttributeNames = array('autocomplete', 'novalidate',);
  /**
   * @var Array: Names of class/instance attribute/members/properties that
   * can be set by initialization
   */
  protected static $instancePropertyNames = array('name', 'label', 'for',);
      //'name_segment', 'name_segments');
  protected static $otherAttributeNames = array('name_segment', 'name_segments');
  protected $attributes = array();
  protected $otherAttributes = array();

  /**
   *
   * @var array: key/value pairs of default attributes if none explicitly set
   * in setValues. For example, for the BaseForm class, are:
   * ('method'=>'post', 'enctype'=>'multipart/form-data', etc...;
   */
  protected static $classDefaultAttributes = array();



  /** @var array: Hack to exclude certain legitimate HTML attribute values from being 
   * set in attrString because we will be handling them specially...
   */
  protected static $valueExclusions = array();
  /**
   *
   * @var String: If set, will be the name of the control. If not set, the
   * name will be build from ->name_segments & ->name_segment
   */
  protected $name = '';
  protected $label = '';
  protected $for = '';
  /**
   *
   * @var Array: Form and control name segments, to be assembled into the
   * HTML input element name, unless the "name" property is set, to override it. 
   * 
   * For instance, for a "User" control, the form
   * name will be 'user', the hidden 'id' element will have the name_segment "id",
   * the name segments
   * will be array('user','id');. If the user object has multiple profiles, say,
   * for profile name, the name_segments might be:
   * array('user','profiles','','name');
   * where the empty string component would be replaced by an index count.
   */
  protected $name_segments = array();
  /**
   *
   * @var String: The current name segment. 
   */
  protected $name_segment = '';
  /**
   * @var Array: Copy of the original arguments to create or set this thing,
   * in case we want create a form instead of an element or v/v  
   */
  protected $origArgs; 

  /**
   *
   * @var mixed -- the 'value' of the control. In most cases, the 'value' 
   * attribute of the HTML control, except for textarea, where it is the content
   * between the open/close <textarea>Content</textarea> tags.
   */
  protected $content;

  /** Is this a form/element to be displayed, or used as basis of a template
   * @var Boolean 
   */
  protected $is_template = false;
  public function __construct($args = null ) {
    $class = get_class($this);
    //pkdebug("CONSTRUCT FOR CLASS [$class]; args:", $args);
    //parent::__construct($args);
    $this->setValuesDefault();

    #set some defaults, then call set Values with rest
    $this->setValues($args);
  }

  /**
   * Has to also figure out whether to set the $this->attributes['value'] or
   * $this->otherAttributes['content'];
   * @param string $content
   */
  protected function setContent($content) {
    if ($this->input == 'textarea') {
      $this->otherAttributes['content'] = $content;
    }
    else {
      $content = static::clean($content);
      $this->attributes['value'] = $content;
    }
    $this->content = $content;
  }

  protected function getContent() {
    return $this->content;
  }

  /**
   * Returns the an indexed array of the array keys of the element as they 
   * would be in PHP $_POST, based on the element name. For example, if
   * $this->getName() is: "user[profiles][4][profile_jobs][2][employer]",
   * Returns: array('user','profiles',4,'profile_jobs',2,'employer');
   * @return array: Indexed array of keys to depth. 
   */
  protected function getKeysFromName() {
    $retarr = array();
    $name = $this->getName();
    $retarr = explode ('[',$name);
    foreach ($retarr as &$retel) {
      if (substr($retel,-1) == ']') {
        $retel = trim($retel, "]") ;
      }
    }
    return $retarr;
  }

  /**
   * Sets any default attributes for this element/form. Called first
   * in ->setValues($args), so explicitly set in the args array, the 
   * defaults are overridden.
   * Uses the class static attribute array:  $classDefaultAttributes
   */
  protected function setValuesDefault() {
    foreach (static::$classDefaultAttributes as $key => $value) {
      if (!array_key_exists($key, $this->attributes)) {
        $this->attributes[$key]=$value;
      }
    }
  }

  /**
   * Performs the actual data array building/recursion
   * TODO: Need to do somethng for collections/scrolling froms.
   * TODO!: But more important, why do we ever need this function?
   * @return array|scalar: The data array or subcomponent
   */
  /*
  public function getValuesRecursive() {
    if ($this instanceOf BaseElement) {
      return $this->getContent();
    }
    $retarr = array();
    foreach ($this->elements as $element) {
      $retarr[$element->getName()] = $element->getValuesRecursive();
    }
    return $retarr;
  }
   * *
   */

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
   * Climbs the ancestor hierarchy of attributes, and adds global HTML
   * attributes
   */
  public static function getValidAttributeNames() {
     $bfcVA = static::getAncestorArraysMerged('validAttributeNames');
     $validAttributeNames = array_flatten($bfcVA,static::$globalHtmlAttributes);
     return $validAttributeNames;
  }

  /**
   * Verifies if the named attribute belongs as an attribute in the input
   * element. Aside from just checking if in_array of validAttributeNames, makes
   * *dispensation for attributes starting with 'data-'.
   * @param String $attr: attribute name
   * @return Boolean: Include in input control?
   */
  public static function isValidAttribute($attr) {
    if (substr($attr,0,5) === 'data-') {
      return true;
    }
    if (in_array($attr, static::getValidAttributeNames())) {
      return true;
    }
    return false;
  }

  /** TODO: BAD method naming here -- get/set operate on two different things 
   *  completely
   * @param type $args
   */
  public static function getinstancePropertyNames() {
     return static::getAncestorArraysMerged('instancePropertyNames');
  }

  /** Two approaches: count on instancePropertyNames being set correctly, or
   * get all object properties and see if arg keys match names....
   * @param type $args
   */
  public function setinstanceProperties($args) {
    #Trust static instancePropertyNames...
    $iprops = static::getInstancePropertyNames();
    $className = get_class();
    //pkdebug("SETTING INSTANCE PROPS for class [$className], PROPS are:", $iprops, "args are:", $args);
    foreach ($args as $key => $value) {
      if (in_array($key, $iprops )) {
        $this->$key = $value;
      }
    }
    //pkdebug("Leaving SetInstanceProperties, this is:", $this);
  }

  public function makeLabelCtl() {
    $labelCtl='';
    if ($this->label) {
      $val = $this->label;
      $for = $this->for ? " for = '{$this->for}' " : '';
      $el_class = $this->getAttribute('class');
      $ctlPairClass = $this->ctl_pair_class; #Can be customized in create
      $labelCtl = "<div class='$ctlPairClass $el_class-pair' >"
        . " <label $for>$val</label> ";
    }
    return $labelCtl;
  }

  /**
   * Returns the control name, built from the name_segments array an the current
   * name. IF you want to override the default name_segments, just set it to null
   * explicitly when creating the control/form.
   * @return String
   */
  public function getName() {
    $class=get_class($this);
    if ($this->name) {
     // pkdebug("CLASS[$class]Returning just this->name: ".$this->name);
      return $this->name;
    }
    $name = '';
    if ($this->name_segment) {
      if (!sizeof($this->name_segments)) {
        $this->name = $this->name_segment;
      //pkdebug("CLASS[$class]No 'segments'; Returning just this->segment_name: ".$this->name);
        return $this->name;
      }
      foreach ($this->name_segments as $name_segment) {
        if (!$name) {
          $name = $name_segment;
        } else {
          $name=$name."[$name_segment]";
        }
      }
      $name = $name."[{$this->name_segment}]";
    }
    $this->name = $name;
    //pkdebug("CLASS[$class] There WERE segments:", $this->name_segments," Returning just this->segment_name: ".$this->name);
    return $name;
  }

  /**
   * Returns the value of any attribute, normal or special, or false if none
   * @param type $attrName
   * @return boolean|value: The attribute value if it exists, else boolean false
   * @throws \Exception: if $attrName not a string
   */
  public function getAttribute($attrName) {
    if (!$attrName || !is_string($attrName)) { #bad call
      throw new \Exception("Improper arg for attrName");
    }
    $attributes = $this->getAllAttributes();
    if (!array_key_exists($attrName,$attributes)) {
      return false;
    }
    return $attributes[$attrName];
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


  /** Sets values for this element
   * If key name is valid attribute, adds to attribute array and
   * cleans its value
   * If any keys match this object member variable attributes as enumerated in
   * static::$instancePropertyNames, sets those
   * And all other keys of unknown provenance are saved to ->otherAttributes.
   * @param array $args: Assoc array of name/value pairs
   * FOR TEXTAREA & BUTTON INPUTS! Must use special val key/name: 'content' to 
   * be inclduded between the open and close tags
   */

  public function setValues(Array $args = array(), $exclusions = array(), $useDefaults = true) {
    if ($useDefaults) {
      $this->setValuesDefault();
    }
    if (!$args || !is_array($args)) {
      return $this;
    }
    $this->origArgs = $args; #Let's keep all the original args, in case...
    if (!empty($args['name_segments'])) {
      $this->name_segments = $args['name_segments'];
    }
    if (!empty($args['name_segment'])) {
      $this->name_segment = $args['name_segment'];
    }
    /*
     * 
     */
    if (!empty($args['name'])) {
      $this->name = $args['name'];
    }
    //$this->name_segments[] = $this->name;

    #Set the "normal" attribute values first
    //$this->setAttributeVals($args);
    $this->setInstanceProperties($args);

    #Args that remain are for special treatment....
    foreach ($args as $key => $val) {
      //if (($key == 'input') || ($key == 'type')) {
      if (in_array($key, static::$valueExclusions) 
        || in_array($key,$exclusions)) {#For some reason, we want to skip these
        continue;
      } else if (static::isValidAttribute($key)) {
        if (!is_string($val)) {
          pkdebug("In ElSetvale, is attribute, key: [$key], value:", $val);
          pkstack (4);
        }
        $this->attributes[$key] = static::clean($val);
      } else { #Leftover args -- save for later?
        #But don't know what they are, so don't clean
        #We'll keep a copy of the original, unclean attribute values here, too
        $this->otherAttributes[$key] = $val;
      }
    }
    #Finalize, after basics
    if (isset($this->attributes['input'])) {
      if (($this->attributes['input'] == 'textarea') 
          && isset($this->otherAttributes['content'])) {
        $this->content = $this->otherAttributes['content'];
      } else if (isset($this->attributes['value'])) {
        $this->content = $this->atttributes['value'];
      }
    }
  }


  /** If any of the arg keys match an instance property name, set it
   * 
   */


  /** Sets the HTML Element Attributes ($this->attributes) from the argument
   * array, unless exception specified in the $exclusions array
   * @param array assoc $args
   * @param array indexed $exclusions
   */
  public function setAttributeVals($args = array(), $exclusions = array()) {

  }
  

  /**
   * Makes an inner attribute string from regular HTML form element attributes
   * @param Array $defaults: Optional array of key/value pairs to set as default
   * values for this element, if values not provided in the constructor
   * @param Array $exclusions: Indexed array of attribute names NOT to include
   * when building attribute string, if overridden in particular control builder
   * @return String: HTML attribute string for inclusion in element/control,
   * like: " name='aname' class='aclass' ... "
   */
  public function makeAttrStr($defaults = array(), $exclusions = array()) {
    //pkdebug ("This Element:", $this);

    //$attrStr = " name='".$this->getName()."' ";
    $attrStr = " name='".$this->getName()."' ";
    $attributes = $this->getAttributes();
    foreach ($defaults as $key => $value) {
      /*
      if ($key == 'name') {
        continue;
      }
       * 
       */
      if (!in_array($key, array_keys($attributes))) {
        $attributes[$key] = $value;
      }
    }
    foreach ($attributes as $key =>$value) {
      if (!in_array($key, $exclusions)) {
        if ($key == 'name') {
          continue;
        }
        $attrStr .= " $key='$value' ";
      }
    }
    return $attrStr;
  }


}

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


class BaseElement extends BaseFormComponent {

  /** An array of valid HTML input attributes - if they are included as param
   * keys, will be part of the input element.
   * Should not be accessed directly, accessed instead by the function
   * "static::isValidAttribute($attrName);" to account for the special cases of
   * 'data-XXX' attributes. isValidAttribute also checks a separate list of
   * HTML Global attributes which are not specific to form elements and not
   * in this array.
   */
  protected static $validAttributeNames = array(
      'accesskey', 'class', 'contenteditable', 'contextmenu', 'dir',
      'draggable', 'dropzone', 'hidden', 'id', 'lang', 'spellcheck',
      'style', 'tabindex', 'title', 'translate',  'name',
      'checked', 'accept', 'alt', 'autofocus',
      'form', 'formaction', 'formenctype', 'formmethod', 'formnovalidate',
      'formtarget', 'height', 'list', 'max', 'maxlength', 'min', 'multiple',
      'step', 'type', 'value', 'width', 'placeholder',
  );

  protected $ctl_pair_class="ctl-pair";

  /** @var array: Hack to exclude certain legitimate HTML attribute values from being 
   * set in attrString because we will be handling them specially...
   */
  protected static $valueExcusions = array('input','type');

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
   * @var array: Names of key attributes from the initialization array that
   * direct members of this class, and assigned directly. 
   */
  protected static $instancePropertyNames = array(
       'ctl_pair_class', 'input', 'type'
      );
  protected static $specialAttributes = array('label', 'for', 'ctl_pair_class');

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
   * 
   * @param Array $ags: Assoc array of key/value pairs. Just initializes values
   * See doc for buildHtml() below for details
   */
  public function __construct($args = array()) {
    parent::__construct($args);
  }

  /** Sets values for this element
   * If key name is valid attribute, adds to attributer array and
   * cleans its value
   * @param array $args: Assoc array of name/value pairs
   * FOR TEXTAREA & BUTTON INPUTS! Must use special val key/name: 'content' to 
   * be inclduded between the open and close tags
   */
  public function setValues(Array $args = array(), $exclusions = array(), $useDefaults = true) {
    parent::setValues($args, $exclusions, $useDefaults);
    if (($this->input == 'input') && !$this->type) {
      $this->type = 'text';
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
    return ''.$this->buildHtml();
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
    $labelCtl=$this->makeLabelCtl();
    $ret[] = $labelCtl;
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
      //$ret[] = static::makeBooleanInput($this->attributes);
      $ret[] = $this->makeBooleanInput();




      /*
    } else if ($input === 'subform') { #Just echo the subform, or build it ...
      $val = '';
      if (isset($this->otherAttributes['content'])) {
        $content=$this->otherAttributes['content'];
        if (is_string($content) || ($content instanceOf BaseForm)) {
          $val = $content;
        } else if (is_array($content)) { #Enhancement: use 'type' for 
          $content['subform'] = true;
          $val = new BaseForm($content); #in case descendent of BaseForm
        } else { #Not valid
          $subformType = typeOf($content);
          throw new \Exception("Invalid subform arg type: [$subformType]");
        }

      } else { #Create the subform with the original arguments to this El...
        $args = $this->origArgs;
        $args['subform'] = true;
        if (isset($args['scrolling'])) {
          $val = new FormSet($args);
        } else {
          $val = new BaseForm($args);
        }
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
       * 
       */




    } else if ($input === 'select') { #Build the select...
      $ret[] = $this->makePicker();
    } else if ($input === 'html') { #Convenience, just echo without question...
      $val = '';
      if (isset($this->otherAttributes['content'])) {
        $val .= $this->otherAttributes['content'];
      } 
      if (isset($this->attributes['value'])) {
        $val .= $this->attributes['value'];
      } 
      $ret[] = $val;
    } else { #Not a special type, make a regular input of the type..
      $ret[] = "\n<$input type='$type' $attrStr />";
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
   * replace the value of the hidden field during a submit. 
   * No called params, but taken from member attributes:
   * @key $name: Required: The field "name" value
   * @ return String: HTML of the paired checkboxes to make a boolean true/false
   */
  public function makeBooleanInput() {
    $defaults = array('class'=>'boolean-checkbox', 'value'=>'1');
    $exclusions = array('type');
    $inputAttributes = $this->getAttributes();
    $otherAttributes = $this->getOtherAttributes();
    $allAttributes = $this->getAllAttributes();
    $optNames = array_keys($allAttributes);
    $reqOpts = array('name');
    if (array_diff($reqOpts, $optNames)) { #Not all req opts present
      $optStr = implode(',',$optNames);
      $reqOptStr = implode(',',$reqOpts); 
      throw new \Exception("Required Options: [$reqOptStr], got: [$optStr] ");
    }
    #We have all required options, set extra attributes
    $name = $this->name;
    $attStr = $this->makeAttrStr($defaults, $exclusions);
    $hiddenStr = " <input type='hidden' value='' name='$name' />";
    $checkStr = " <input type='checkbox' $attrStr />";
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
  public function makePicker() { #$name, $key, $val, $arr, $selected = null, $none = null) {
    $name = $this->name;
    $defaults = array('class', "$name-sel");
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
}

class BaseDbElement extends BaseElement {
  
}
