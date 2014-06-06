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
 * Description of PKMVCForm
 */

/** Maps control names to object properties
*/
namespace PKMVC;


/** Takes submitted post data and populates an object(s).
 * 
 * By default, 
 * Assumes the "form" data is in the form of an array possibly 
 * multi-dimensional for objects with collections (eg, shopping cart with 
 * items)
 * 
 * For example, if the object is of class "ShoppingCart", the underlying
 * table will be "shopping_cart". The POST array will contain the keys:
 * shopping_cart[id], shopping_cart[name], etc. If the ShoppingCart class 
 * contains a collection member "items", the form/POST array will contain:
 * "shopping_cart[items][0][id], shopping_cart[items][0][price]...."
 * 
 * If the user deleltes all the items in the cart, "shopping_cart[items]" index
 * in the post array won't be set, so you might want to delete all the items
 * from the cart. 
 * 
 * However, another form might also manipulate the cart, but not deal with the
 * items at all, in which case the "[items]" index will also not be set, but
 * in this case you DON'T want to delete the items. 
 * 
 * We would like to have the same form handling code as much as possible,
 * however, so our convention is on forms that are meant to add/delete items,
 * create a hidden input name="shopping_cart[items]" value="".
 * 
 * This way, if the cart has been emptied, the "items" index will exist but be
 * empty, indicating deletion of all items in the cart. If there are items in 
 * the cart, those inputs will override the empty "items", and can be persisted.
 * 
 * And in case of another cart manipulation form that doesn't deal with items,
 * the items index will never be set, and therefore not deleted from the object.
 * 
 * BaseForm can be extended to include particular controls, and a dedicated
 * template.
 * 
 * The BaseForm class also tries to do some clever things to handle common
 * situations - like if the form maps to an object/class, you can instantiate
 * the base form with an object. In which case it will create a default
 * hidden input element with the name "objName[id]", set it to the value of the
 * object ID, and output it with the ->openForm() method.
 * 
 * TODO: Figure out how to integrate this with repeating
 * subforms/collections...
 */

class BaseForm {
  /**
   *
   * @var String: The template to use with the particular form instance
   */
  protected $template = '';
  /**
   * @var BaseModel: Often, a form will be based on a BaseModel object. In
   * which case, set it and we do some assistance.
   */
  protected $baseObj = null;

  /**
   * @var String: The various form attributes and their defaults...
   */
  protected $action = '';
  protected $enctype = 'multipart/form-data';
  protected $method = 'POST';
  protected $name = '';
  protected $class = '';
  protected $id = '';
  protected $renderResult = null;

  /**
   *
   * @var Array: An optional associative array of data to be rendered with the
   * associated template, if rendering directly from the form instance.
   */
  protected $renderData = array();

  /**
   * @var Array: Ass Array of PKMVC Element/Form Input elements as name=>$el
   * Elements may be atomic HTML elements, or subforms containing other elements
   */
  protected $elements = array();

  /** The $formData should be a clean array of data, with relevant
   * class names as the array keys to the data
   * 
   * IF THE FORM CONSISTS OF ADDITIONAL SUBFORMS AND REPEATING SUBFORMS, ONLY
   * THE TOP LEVEL FORM SHOULD EXECUTE THIS METHOD!
   * 
   * @param array $formData
   * @param string $action: method - what should be done with this data? 
   * Default: if empty/false, create object/save. 
   * @param boolean $save (default: true): Should the object be saved now?
   * In some cases, like user registration, might need additional processing 
   * before saving?
   * @return type array of saved objects
   */

  public function setBaseObj($baseObj) {
    $this->baseObj = $baseObj;
    return $this->baseObj;
  }

  public function getBaseObj() {
    return $this->baseObj;
  }

  public function __construct(BaseModel $baseObj = null) {
    $this->baseObj = $baseObj;
  }

  public function submitToClass(Array $formData, $action=null, $save=true) {
    pkdebug("Submitting:", $formData);
    $results = array();
    $formData = htmlclean($formData);
    $classNames = array_keys($formData); 
    foreach ($classNames as $className) {
      pkdebug("Trying to make/save an object of:  [$className] with data: ", $formData[$className]);
      $obj = $className::get($formData[$className]);
      pkdebug("The Object is:", $obj);
      if ($save) {
        $obj->save();
      }
      $results[]= $obj;
    }
    return $results;
  }

  /**
   * Return the open form string, based on attributes
   */
  public function openForm($echoId = true) {
    $formTag = "\n<form class='{$this->class}' method='{$this->method}'
      action='{$this->action}' id={$this->id}' enctype='{$this->enctype}' 
        name='{$this->name}' >\n";
    if ($echoId) {
      $formTag .= $this->getElement('id');
    }
    return $formTag;
  }

  /** Seems ridiculous, but for symetry...
   * 
   * @return string: Just the form close tag...
   */
  public function closeForm() {
    return "\n</form>\n";
  }

  /** Add a PKMVC Form element to the assoc array collection, as name=>object
   * pairs. Can be one at a time (if $key is a string) or multiple (if array)
   * @param String|Array $key: Either the string key name, or an array of keys
   * and valuues. Values can be either instances of BaseElements, or arrays of
   * values used to build an element
   * @param BaseElement|null $val: Individual BaseElement instance, or null
   */
  public function addElement($key, $val=null) {
    $setArr = array();
    if (is_array($key)) {
      $setArr = $key;
    } else if (is_string($key)) {
      $setArr[$key] = $val;
    } else { #Bad input
      throw new \Exception("Bad key input to add Element value");
    }
    foreach ($setArr as $skey => $sval) {
      if ($sval instanceOf BaseElement) {
        $this->elements[$skey] = $sval;
      } else if (is_array($sval)) { #Make an element from the data
        $el = new BaseElement($sval);
        $this->elements[$skey] = $el;
      } else { 
        $this->elements[$skey] = $sval;
      }
    }
    return $this->elements;
  }

  /**
   * If the instance has an instantiated ::$renderResult member, or $resultData
   * and $templateMembers, render.
   */
  public function __toString() {
    if (!($this->renderResult)) {
      $this->renderResult = new RenderResult($this->renderData, $this->template);
    }
    return $this->renderResult->__toString();
  }

  /** Returns an input element by name in assoc array
   * 
   * @param String $name
   */
  public function getElement($name) {
    if (isset($this->elements[$name])) {
      return $this->elements[$name];
    } else if (($this->baseObj) && ($name == 'id')) {
      $className = $this->baseObj->getBaseName();
      return new BaseElement(array('type'=>'hidden',
          'name'=>unCamelCase($className)."[id]", 'value'=>$this->baseObj->getId()));

    } else {
      return null;
    }
  }


  ########## Methods to support repeating/scrolling forms/subforms - with
  ### support for templates. Uses supporting JS library

  
/**
 * Sets up repeating subforms/collections for form display/processing, and
 * calls $this->editSubformItem() n+1 times to present the n existing items
 * in the collection, and a blank template to add additional items (implemented
 * through JavaScript). Adds a "New Item" button for the collection.
 *
 * Whatever calls this method should have a view template to inject the results
 * into.
 *
 * @param String $collName: The name of the collection as represented in
 * the containing object. Ex, containing class is "Mom", collection is
 * "Mom->$kids", $collName arg is "kids".
 * @param String $itemType: The Model Class/type. If collection name is "kids", the
 * item type might be: "Person"
 * @param String $itemTemplate: the name of the template to use to display the 
 * subform of $item components.
 *
 * TODO: Make generally recursive, for deep nesting, if desired. Totally missing 
 * an abstraction layer which needs to be implemented at some point.
 *
 * @param Array $items: Array/Collection of subform objects
 * @return Array: The associative data array of results. Each key should be echoed 
 * in a template, each value should be echoable -- that is, be a string or have a
 * __toString implementation. The class PartialSet, for example, extends ArrayObject,
 * and implements __toString by calling __toString on every element.

 
 
 The HTML string representing the subform, with existing items,
 * create/delete buttons, etc.
 */

public static function multiSubFormsSetup($collName, $itemType, $itemTemplate = null, Array $items = array()) {
  $data = array();
  $data[$collName] = new PartialSet();
  $seqName = $collName.'_idx';
  $idx = 0;
  if (!empty($items) && sizeof($items)) { #Got something...
    foreach ($items as $item) {
      if ($item instanceOf BaseModel) {
        $itemEls = static::editSubformItem($itemType, $item, $idx);
        $data[$collName][] = new RenderResult($itemEls, $itemTemplate);
          //ApplicationBase::exec('magicpartial', 'editfoodgroupconsideration', $foodgroupconsideration, $idx);
          //new static ($collName, $collType, $item, $idx);
        $idx++;
      }
    }
  }
  $data[$seqName] = $idx;
  $data['idx'] = $idx;
  #Make an item template:
  $item_tpl = static::editSubformItem($itemType);
  $data[$collName.'_template'] = new RenderResult(array("item_data"=>(new RenderResult($item_tpl, $itemTemplate)),'idx'=>'__template__'), 'forms/baseitem');
  $data['item_form_template'] = new RenderResult(array("item_data"=>(new RenderResult($item_tpl, $itemTemplate)),'idx'=>'__template__'), 'forms/baseitem');
  return $data;
}


/**
 * The individual row/subform management/display. Called by ::multiSubformsSetup(),
 * to display an individual component of the collection, and a hidden template.
 *
 * @return Array: Associative array of data, to be later included into a RenderResult with 
 * a view template.
 */

  public static function editSubformItem($itemType, BaseModel $item = null,  $idx='__template__') {
    $data = array();
    $seqName = $itemType."_idx";
    $data[$seqName] = $idx;
    $data['idx'] = $idx;
    //If no item object, make a subform template
    if (empty($item) || !($item instanceOf BaseModel)) { //make template
      $item =  $itemType::get(); #Make new empty instance
    }
    $data['item'] = $item;
    $data['id'] = $item->getId();
    //$data['fieldsToShow'] = $this->fieldsToShow;
    return $data;
  }
   
}

/**
 * Returns a string containing two HTML input elements of the same name -- a hidden
 * input field followed by a check-box. The value of the hidden field will always
 * be set to the emmpty string. If the checkk box is checked, its value will
 * replace the value of the hidden field during a submmit. 
 * @param $name: Either a string representing tthte "name" value, or an array of HTML
 * attributes for the elements. 
 * @param $value: The value of the checkox. If empty, just One. The hidden will
 * always be the empty string.
 ^ @ return String: HTML of the paired checkboxes to make a boolean true/false
 */

function makeBooleanInput ($name, $checked = false, $value=null) {
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
