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
 * IMPORTANT: A form can be a top-level form, or subform -- including 
 * scrolling one-to-many subform collection of repeating elements. 
 */

class BaseForm extends BaseFormComponent {
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
  /*
  protected $action = '';
  protected $enctype = 'multipart/form-data';
  protected $method = 'POST';
  protected $name = '';
  protected $class = '';
  protected $id = '';
   * 
   */
  protected $renderResult = null;

  /** An array of valid HTML FORM attributes - if they are included as param
   * keys, will be part of the form element attribute set..
   * This array shouldn't be accessed directly, accessed instead by the function
   * "static::isValidAttribute($attrName);" to account for the special cases of
   * 'data-XXX' attributes. isValidAttribute also checks a separate list of
   * HTML Global attributes which are not specific to form elements and not
   * in this array.
   */
  public /*protected*/ static $validAttributes = array (
      'action', 'enctype', 'method', 'name', 
  );

  /**
   * @var array: Names of key attributes from the initialization array that
   * direct members of this class, and assigned directly. 
   * 'type' here is ?
   * subform: default false, top level form, so have form open/close tags,
   * scrolling: default false; if true, repeating form, array of objs
   */
  public /*protected*/ static $memberAttributes = array(
       'subform', 'scrolling', 'template', 'baseObj', 'type',

      );

  /**
   * @var type Is this the top level form - that is, not a subform? 
   */
  protected $subform = false;
  protected $scrolling = false;

  /**
   * @var boolean|String: If string, contains a copy of the rendered form as
   * a JS template, for scrolling forms
   */
  protected $js_template = false;

  /**
   *
   * @var array: $key=>$value pairs of form attributes & values (like,
   * class, action, method, etc)
   */
  protected $attributes = array(); 

  /**
   *
   * @var Array: An optional associative array of data to be rendered with the
   * associated template, if rendering directly from the form instance.
   */
  protected $renderData = array();

  /**
   * @var Array: Ass Array of PKMVC Element/Form Input elements as name=>$el
   * Elements may be atomic HTML elements, or subforms containing other elements
   * One of the BaseElement types you can add is input='html', which just
   * outputs the raw HTML as content - so you can add HTML like div's and
   * other content as elements, and maybe skip the need for a form template
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

  /** Overrides base method just for special treatment of ->baseObj, 
   * (in case class name & not object makes new), then hands off to parent.
   * @param Array $args: The initialization args
   */
  public function setMemberAttributeVals($args) {
    if (isset($args['baseObj'])) {
      $args['baseObj'] = $this->returnObject($args['baseObj']);
    }
    return parent::setMemberAttributeVals($args);
  }

  /** Takes the argument and returns it if instance of BaseModel, or tries
   * to create it if $obj is a string ClassName.
   * For sure going to have Namespace issues here. Namespaces in PHP suck...
   * @param String|BaseModel: A BaseModel instance or BaseModel class name
   * @return BaseModel: instance of a BaseModel derived class
   */
  public function returnObject($obj) {
    if ($obj instanceOf BaseModel) {
      return $obj;
    }
    if (isset($args['baseObj']) && is_string($args['baseObj'])) {
      $className = toCamelCase($args['baseObj'], true);
      if (!class_exists($className)) { #Bad Model Class
        throw new \Exception("Class [$className] not defined!");
      }
      $baseObj = new $className();
      if (!($baseObj instanceOf BaseModel)) {
      }
      $args['baseModel'] = $baseModel;
    }
    if (is_string($obj)) {
      $className = toCamelCase($obj, true);
      if (!class_exists($className)) { #Bad Model Class
        throw new \Exception("Class [$className] not defined!");
      }
      $newObj = new $className();
    } else {
      throw new \Exception("Bad argument type");
    }
    if (!$newObj instanceOf BaseModel) {
      throw new \Exception("baseObj [$className] not instanceOf BaseModel!");
    }
    return $newObj;
  }

  /**
   * Sets the attributes of the form
   * @param array $attributes: Associative array of attribute names/values
   * If "elements" is a key, the value must be an assoc array of name/value
   * pairs, where the name key is the form element name and the value is either
   * an element instance or an array that can be used to create the element
   * @return \PKMVC\BaseForm
   */
  public function setValues(Array $attributes) {
    parent::setValues($attributes);
    #Set elements if present....
    if (isset($attributes['elements'])) {
      $this->addElement($attributes['elements']);




      $elements = $attributes['elements']; 
      foreach ($elements as $key => $value) { 
        if ($value instanceOf BaseFormComponent) {
          $this->elements[$key] = $value;
        } else if (is_array($value)) {
          $this->elements[$key] = new BaseElement($value);
        } else { #Bad element value
          $elType = typeOf($value);
          throw new \Exception("Invalid el type: [$elType] for key: [$key]'");
        }
      }


    }
    return $this;
  }


  /** Initialize with a model object, or an assoc key/value array
   * 
   * @param \PKMVC\BaseModel|Array $args: If assoc array, keys should
   * correspond to member attribute names. Can include array of elements
   * @return null;
   */
  public function __construct($args = null) {
    $this->elements = new PartialSet();
    if (!$args) {
      return;
    }
    if ($args instanceOf BaseModel) {
      $this->baseObj = $baseObj;
      return;
    }
    if (is_array($args) ) {
      $this->setValues($args);
    }
  }

  public function submitToClass(Array $formData, $action=null, $save=true) {
    //pkdebug("Submitting:", $formData);
    $results = array();
    $formData = htmlclean($formData);
    $classNames = array_keys($formData); 
    foreach ($classNames as $className) {
      pkdebug("Trying to make/save an object of:  [$className] with data: ", $formData[$className]);
      $obj = $className::get($formData[$className]);
      if ($save) {
        $obj->save();
      }
      $results[]= $obj;
      //Debug....
      break;
    }
    //return $results;
      //pkdebug("The RETURNING Object is:", $obj);
    return $obj;
  }

  /**
   * Return the open form string, based on attributes
   * If don't want to automatically echo the ID element, $echoId = false;
   * @param boolean $echoId: Should the openForm method automatically echo
   * a hidden ID element, and generate it if it's not already set?
   */
  public function openForm($echoId = true) {
    /*
    $formTag = "\n<form class='{$this->class}' method='{$this->method}'
      action='{$this->action}' id={$this->id}' enctype='{$this->enctype}' 
        name='{$this->name}' >\n";
     * 
     */
    $attrStr = $this->makeAttrStr();
    $formTag = "\n<form $attrStr >";
    if ($echoId) {
      $formTag .= $this->getElement('id');
      unset($this->elements['id']); #If already output here, don't do again
    }
    return $formTag;
  }

  /** Close tag, and default "submit" button if none set. Can negate outputting
   * any submit button by adding an empty/null element named "submit".
   * 
   * @return string:  HTML  form close tag, and submit button...
   */
  public function closeForm() {
    $close =  "\n</form>\n";
    $submitEl = $this->getElement('submit');
    if ($submitEl === false) { #Not set; not even to null! So create default
      $submitEl = new BaseElement(array('submit'=>
          array('type'=>'submit', 'name'=>'submit', 'value'=>'Submit')));
    }
      if ($submitEl) { #submitEl 
        $close = "\n".$submitEl.$close;
    }
    return $close;
  }

  /** Add a PKMVC Form element to the assoc array collection, as name=>object
   * pairs. Can be one at a time (if $key is a string) or multiple (if array)
   * @param String|Array $key: Either the string key name, or an array of keys
   * and values. Values can be either instances of BaseFormComponent
   * (eg, BaseForm or BaseElement) or arrays of values used to build an
   * element or subform.
   * @param BaseFormComponent|null $val: Individual BaseFormComponent
   * instance, or null if the $key parameter is an array
   */
  public function setElement($key = null, $val=null) {
    return $this->addElement($key,$val);
  }
  public function addElement($key = null, $val=null) {
    if (!$key) {
      return $this->elements;
    }

    #Is $key array of names/elements, or a string name, with an element value?
    #Either way, convert to array so we only deal with one method
    $setArr = array();
    if (is_array($key)) {
      $setArr = $key;
    } else if (is_string($key)) {
      $setArr[$key] = $val;
    } else { #Bad key
      $keyType = typeOf($key);
      throw new \Exception("Bad key type [$keyType] to add Element value");
    }

    #Okay, have an array. But good element name/element value (El or arr) pairs?
    foreach ($setArr as $skey => $sval) {
      if (!is_string($skey) && !is_numeric($skey)) { #String -- or num?
        $skeyType = typeOf($skey);
        throw new \Exception("Bad key type [$skeyType] to add Element value");
      }
      if ($sval instanceOf BaseFormComponent) {#Is already el or subform...
        $this->elements[$skey] = $sval;
      } else if (is_array($sval)) { #Make element or subform from data array
        if (isset($sval['subform'])) {
          if (isset($sval['scrolling'])) {
            $this->elements[$skey] = new FormSet($sval);
          } else {
            $this->elements[$skey] = new BaseForm($sval);
          }
        } else {
          $this->elements[$skey] = new BaseElement($sval);
        }
      } else { #Bad element value 
        $svalType = typeOf($sval);
        throw new \Exception("Bad El type [$svalType] for Key: [$skey]");
      }
    }
    return $this->elements;
  }

  public function setTemplate($template) {
    return ($this->template = $template);
  }
  public function getTemplate() {
    return $this->template;
  }
  public function setRenderResult(RenderResult $renderResult) {
    return ($this->renderResult = $renderResult);
  }
  public function getRenderResult() {
    return $this->renderResult;
  }
  /**
   * If the instance has an instantiated ::$renderResult member, or $resultData
   * and $templateMembers, render.
   */
  public function __toString() {
    if ($this->getRenderResult()) {
      return $this->renderResult->__toString();
    }
    if ($this->template) {
      if (!($this->renderResult)) {
        $this->renderResult = new RenderResult($this->renderData, $this->template);
      }
      return $this->renderResult->__toString();
    }
    #No template, no renderResult - output default, which is all elements  
    #BUT -- if it is topLevel form, output open & close tags as well....
    if (!$this->subform) {
      return $this->openForm().$this->elements.$this->closeForm();
    }
    return ''.$this->elements;
  }

  /** Returns an input element by name in assoc array. Can be empty/null if
   * explicitly set to null, or returns boolean false if not set at all.
   * 
   * @param String $name
   */
  public function getElement($elName) {
    if (array_key_exists($elName, $this->elements)) {
      return $this->elements[$elName]; #Distinguish between explicitly set NULL and not set at all
    }
    if ($elName == 'id') {#Not explicitly set or cleared, so make default..
      if ($this->baseObj) {
        $className = $this->baseObj->getBaseName();
        return new BaseElement(array('type'=>'hidden',
            'name'=>unCamelCase($className)."[id]", 'value'=>$this->baseObj->getId()));

      } else {
      }
    }
  }


  /**
   * Builds a default form from an object/class instanceOf BaseModel
   * @param null|BaseModel|String $obj: An object instance of BaseModel, or
   * a String which is the name of a class descendent from BaseModel. If
   * null, looks for the current form instance $this->baseObj;
   * @param array $args: Optional key/value arguments -- like, maybe a template?
   */
  public function spinFormFromObject($obj = null, $args = array()) {
    $baseObj = $this->baseObj; #default
    if ($obj) { #override the member default baseObj
      $baseObj = $this->returnObject($obj);
    }
    if (!$baseObj instanceOf BaseModel) {
      throw new \Exception("Couldn't get a valid baseObject");
    }
    #Have the object, now the hard part....
    #Build the array of names and values

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
 * Collection of identical forms for collections/scrolling/add/delete...
 * TODO: What are the args we should use here?
 */
class FormSet extends BaseForm {
  /**
   *
   * @var PartialSet: collection of identical forms (except for content)
   * Want as PartialSet instead of just array to allow __toString()
   */
  protected $forms = null;

  /**
   * @var Array: The set of objects to match to the forms, or array of 
   * data arrays if not objs
   */
  protected $data = array();
  protected $objs = array();
  /**
   *
   * @var BaseForm: The base form instance to clone & populate
   */
  protected $base_form;
  public /*protected*/ $otherAttributeNames = array('base_form', 'objs', 'data');

  public function __construct($args = null) {
    $this->forms = new PartialSet();
    parent::__construct($args);

  }

}