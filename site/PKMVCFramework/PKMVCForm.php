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
 * 
 * To initialize a form in creation,($form = new BaseForm($argArray), 
 *  or by creating an empty ($form = new BaseForm())
 * and then calling $form->setValues($argArray), the $argArray accepts the 
 * following format:
 * An associative array of key values. The primare keys are any valid HTML
 * form attributes ('class', 'id', 'name', etc), as well as these additional
 * primary keys:
 * 'elements': An associative array of BaseElement names with BaseElement 
 * constructor arrays. Like: ... '
 * elements' = array('myelname' => array('input'=>'textarea', 'class'=>'txt-a',
 *   'placeholder'=>'Tell us about yourself', ....)
 * 
 * If a 'label' key is present, the control/element will be wrapped in a div
 * and the label will be a component.
 * 
 * If 'name_segment' is given, it will be used to build the control name with 
 * the 'name_segements' array that is passed automatically.
 * 
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
  protected $base_object = null;
  /**
   *
   * @var String: If this form/subform is tied to a class/object - the className
   */
  protected $base_class = null;

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
  protected static $classDefaultAttributes = array(
      'enctype'=>'multipart/form-data',
      'method' => 'POST',
      );
  protected $renderResult = null;

  /** An array of valid HTML FORM attributes - if they are included as param
   * keys, will be part of the form element attribute set..
   * This array shouldn't be accessed directly, accessed instead by the function
   * "static::isValidAttribute($attrName);" to account for the special cases of
   * 'data-XXX' attributes. isValidAttribute also checks a separate list of
   * HTML Global attributes which are not specific to form elements and not
   * in this array.
   */
  protected static $validAttributeNamess = array (
      'action', 'enctype', 'method', 'name', 
  );

  /**
   * @var array: Names of key attributes from the initialization array that
   * direct members of this class, and assigned directly. 
   * 'type' here is ?
   * subform: default false, top level form, so have form open/close tags,
   * scrolling: default false; if true, repeating form, array of objs
   */
  protected static $instancePropertyNames = array(
       'subform', 'scrolling', 'template', 'base_object', 'type',
      'base_class',

      );

  /**
   * @var type Is this the top level form - that is, not a subform? 
   * The subform value can be boolean "true", in which case nothing automatic
   * is done. Or, if we are creating a subform baased on an object with a
   * collection property, the passed in arg value of subform="$collectionName",
   * in which case automatic stuff will be done, like creating a hidden input
   * with the foreign key name set to the baseObject id....
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
  protected $elements = '';

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

  protected function getElements() {
    return $this->elements;
  }

  public function setBaseObject($base_object) {
    $this->base_object = $base_object;
    if (is_object($base_object)) {
      $this->base_class = class_name($base_object);
    }
    return $this->base_object;
  }

  public function getBaseObject() {
    if (!($this->base_object) && $this->base_class){
      $base_class = $this->base_class;
      $this->base_object = $base_class::get();
    }
    return $this->base_object;
  }

  /** Overrides base method just for special treatment of ->base_object, 
   * (in case class name & not object makes new), then hands off to parent.
   * @param Array $args: The initialization args
   */
  public function setInstancePropertyVals($args) {
    if (isset($args['base_object'])) {
      $args['base_object'] = $this->returnObject($args['base_object']);
      $args['base_class'] = get_class($args['base_object']);
    }
    return parent::setInstancePropertyVals($args);
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
    if (is_string($obj)) {
      $className = toCamelCase($obj, true);
      if (!class_exists($className)) { #Bad Model Class
        throw new \Exception("Class [$className] not defined!");
      }
      $newObj = $className::get();
    } else {
      throw new \Exception("Bad argument type");
    }
    if (!$newObj instanceOf BaseModel) {
      throw new \Exception("base_object [$className] not instanceOf BaseModel!");
    }
    return $newObj;
  }

  /**
   * Sets the attributes of the form, and the elements, if that key is set.
   * If the attribute key "name" is set, the value must either be a string,
   * or an instance of BaseModel, in which case the base class name of the 
   * instance will be used as the form name and root name of the elements, and
   * the base_object of the form will be set to the instance.
   * 
   * @param array $attributes: Associative array of attribute names/values:
   *   [elements]: An associative array of the input elements for the form
   *   [instance]: The BaseModel derived instance associated with the form.
   *       If there is no 'name' key in args, and if 'name' is not already set,
   *       the base class name of the instance will be used for the name of
   *       the form and the root of the element names in the form (for posting)
   *   [{attribute names}]: Other HTML Attribute names, to be included in the
   *        form tag
   *   [{otherAttributes}]: Any keys not otherwise matched are added to the 
   *        $this->otherAttributes array
   * 
   * If "elements" is a key, the value must be an assoc array of name/value
   * pairs, where the name key is the form element name and the value is either
   * an element instance or an array that can be used to create the element
   * @return \PKMVC\BaseForm
   */
  public function setValues(Array $args = array(), $exclusions = array(), $useDefaults = true) {
    static $count = 0;
    if (isset($args['subform'])) {
      $useDefaults = false;
    }
    parent::setValues($args, $exclusions, $useDefaults); #Takes care of the regular attributes
    #Set elements if present....
    if (isset($args['elements'])) {
      $elements = $args['elements'];
      pkdebug("this->namesegment: ". $this->name_segment, "SEGMENTS?", $this->name_segments);
      $name_segments = $this->name_segments;
      $name_segments[] = $this->name_segment;
      foreach ($elements as &$element) {
        $element['name_segments'] = $name_segments;
      }
      $this->addElement($args['elements']);



      /*
      $elements = $args['elements']; 
      foreach ($elements as $key => $value) { 
        #if ($key == 'name') {
        #} else 
        #if ($value instanceOf BaseFormComponent) {
        #  $this->elements[$key] = $value;
        #} else 
         if (is_array($value)) {
          $name_segments = $this->name_segments;
          $name_segments[] = $this->name_segment;
          $value['name_segments'] = $name_segments;
          if (isset($value['subform'])) {#Making a subform
            if (is_string($value['subform'])) {#A collection property
              $collectionName = $value['subform'];
              $obj = $args['base_object'];
              $objMemberCollections = $obj->getMemberCollections();
              $collectionAttr = $objMemberCollections[$collectionName];
              $value['name_segment'] = $collectionName;

              $value['name_segments'][] = $this->name_segment;
              $value['elements'][]=array('type'=>'hidden',
                  'name_segment'=>$collectionAttr['foreignkey'],
                  'base_class'=>$collectionAttr['classname'],
                  );

              //$value['elements'][];
            }
            if (isset($value['scrolling'])) {
              $this->elements[$key] = new FormSet($value);
            } else { #Just a subform, no collection? What's the point?
              $this->elements[$key] = new BaseForm($value, true);
            }
          } else {
            $this->elements[$key] = new BaseElement($value);
          }
        } else { #Bad element value
          $elType = typeOf($value);
          throw new \Exception("Invalid el type: [$elType] for key: [$key]'");
        }
      }



*/















    }
    return $this;
  }


  /** Initialize with a model object, or an assoc key/value array, or nothing.
   * If nothing, still sets the default values like method and enctype.
   * 
   * IF ARGS ARRAY IS GIVEN: Must include a 'name' key, which can be either
   * a string name, or an instance of BaseModel, in which case the object will
   * be stored with the form and the base class name will be used for both 
   * the form name, and the root of the element 'names', to set the keys
   * of the eventual "POST"/submission.
   * 
   * @param \PKMVC\BaseModel|Array $args: If assoc array, keys should
   * correspond to member attribute names. Can include array of elements
   * @return null;
   */
  public function __construct($args = array()) { //, $subform = false) {
    $this->elements = new PartialSet();
    parent::__construct($args);
    if (!$args) {
      return;
    }
    if (!empty($args['subform'])) {
      $this->subform = $args['subform'];
      //pkdebug("Building a subform with args:", $args);
      //pkstack(2);
    }

    /*
    if ($args instanceOf BaseModel) {
      $this->base_object = $args;
      $name = $args->getBaseName();
      $this->setValuesDefault();
      return;
    }
     */
    #Set defaults if not set -- id field and submit button
    if (!$this->subform) {
      $idEl = $this->getElement('id');
      #If don't want default, set explicitly to null. Else, if undefined,
      #returns strict boolean false
      if ($idEl === false) {
        $className = $this->name;
        $id = '';
        if ($this->base_object) {
          $className = $this->base_object->getBaseName();
          $id = $this->base_object->getId();
        }
        $idEl = new BaseElement(array(
          'data-here'=>'auto-id', 
          'type'=>'hidden',
          'name'=>unCamelCase($className)."[id]", 'value'=>$id
        ));
        $this->elements['id'] = $idEl;
      }
      $submitEl = $this->getElement('submit');
      #If don't want default, set explicitly to null. Else, if undefined,
      #returns strict boolean false
      if ($submitEl === false) {
        $submitEl = new BaseElement(array(
            'type' => 'submit',
            'name' => 'submit',
            'value' => 'Submit',
            'class' => 'submit button',
        ));
        $this->elements['submit'] = $submitEl;
      }
    }
  }

  public static function submitToClass(Array $formData, $action=null, $save=true) {
    $results = array();
    $formData = htmlclean($formData);
    $classNames = array_keys($formData); 
    foreach ($classNames as $className) {
      $id = 0;
      if (isset($formData[$className]['id'])) {
        $id = $formData[$className]['id'];
      }
      $obj = $className::get($id);
      $obj->update($formData[$className]);
      if ($save) {
        $obj->save();
      }
      $results[]= $obj;
      //Debug....
      break;
    }
    return $obj;
  }

  /**
   * Return the open form string, based on attributes
   * If don't want to automatically echo the ID element, $echoId = false;
   * @param boolean $echoId: Should the openForm method automatically echo
   * a hidden ID element, and generate it if it's not already set?
   */
  public function openForm($echoId = true) {
    $attrStr = $this->makeAttrStr();
    $formTag = "\n<form $attrStr >\n";
    return $formTag;
  }

  /** Close tag, and default "submit" button if none set. Can negate outputting
   * any submit button by adding an empty/null element named "submit".
   * 
   * @return string:  HTML  form close tag, and submit button...
   */
  public function closeForm() { return "\n</form>\n"; }



  /**
   * Get form data as an associative array
   * @return array: Return the element values as named array, suitable for
   * updating an object (nested) or whatever data you are updating...
   */
  public function getAsArray() {
    $retarr = array();
    $retarr[$this->getName()] = $this->getValuesRecursive();
    return $retarr;
  }

  /**
   * Updates form element values with data array, with names as keys, and 
   * implements collections, etc.
   * @param array $data: The assoc array of data
   * (usu. from a model object, but whatever) to
   * update the values of the form
   */
  public function setAsArray(Array $data) {
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
      /*
        if (!isset($val['name_segments'])) {
          $val['name_segments'] = $this->name_segments;
        } else {
          $this->name_segments = $val['name_segments'];
        }
       * */
        /*
        $val['name_segments'][]=$this->name_segment;
        if (!isset($val['name'])) {
          $val['name'] = $this->getName()."[{$this->name_segment}]";
        }
         * 
         */
        $name_segments = $this->name_segments;
        //$name_segments[]=$this->name_segment;
        if (isset($sval['subform'])) { #Making a subform...
          #$sval['name_segments'] = $this->name_segments;
          #$sval['name_segment'] = $this->name_segment;

          if (empty($sval['class'])) {
             $sval['class'] = ' ';
          }
          $sval['class'] .= ' '.$sval['subform'];
          //pkdebug("Trying to make a subform? With sval:", $sval);
          if (isset($sval['scrolling'])) {
            $this->elements[$skey] = new FormSet($sval);
          } else {
            $this->elements[$skey] = new BaseForm($sval);
          }
        } else {
          
          $name_segments[] = $this->name_segment;
          $sval['name_segments'] = $name_segments;
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
   * If form based on underlying object, bind/set the values from the object
   * to the form element values. 
   * @param array|null |BaseModel $arg: If null, automatically bind to associated object
   * fields. If $data is an instantiated array, assume this is not auto form -
   * If an object, get the data array from that.
   * just try to match element names to data key/values
   */
  public function bind($arg = null) {
    if (!$arg) {
      $data = $this->getBaseObject()->getArrayCopy();
    } else if ($arg instanceOf BaseModel) {
      $data = $arg->getArrayCopy();
    } else if (is_array($arg)) {
      $data = $arg;
    } else {
      throw new \Exception("Bad arg to bind: [".print_r($arg, true).']');
    }
    #Data should now be an appropriate associative array of values
    $elements = $this->getElements();
    $this->bindRecursive($elements, $data);
    /*
    foreach ($data as $datum) {#Iterate & recurse to subforms
      $elName = $element->getName();
    }
     */
  }

  public function bindRecursive($elements, $data) {

  }
  /**
   * If the instance has an instantiated ::$renderResult member, or $resultData
   * and $templateMembers, render.
   */
  public function __toString() {
    #About to display, NOW we set values...
    if (isset($this->base_object)) {#Iterate props and set element vals
      //$this->bind();
    }
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
    } #Ah, so we do have a subform...
    $attrStr = $this->makeAttrStr();

    return "<div data-nonsens='here' $attrStr>".$this->elements."</div>";
  }

  /** Returns an input element by name in assoc array. Can be empty/null if
   * explicitly set to null, or returns boolean false if not set at all.
   * 
   * @param String $name
   */
  public function getElement($elName) {
    if (array_key_exists($elName, $this->elements)) {
      return $this->elements[$elName]; #Distinguish between explicitly set NULL and not set at all
    } else {
      return false;
    }
  }


  /**
   * Builds a default form from an object/class instanceOf BaseModel
   * @param null|BaseModel|String $obj: An object instance of BaseModel, or
   * a String which is the name of a class descendent from BaseModel. If
   * null, looks for the current form instance $this->base_object;
   * @param array $args: Optional key/value arguments -- like, maybe a template?
   */
  #TODO: Cancel! Nice notion, not sustainable now....
  /*
  public function spinFormFromObject($obj = null, $args = array()) {
    $base_object = $this->base_object; #default
    if ($obj) { #override the member default base_object
      $base_object = $this->returnObject($obj);
    }
    if (!$base_object instanceOf BaseModel) {
      throw new \Exception("Couldn't get a valid base_object");
    }
    #Have the object, now the hard part....
    #Build the array of names and values
    

  }
   * 
   */


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
 * create/delete buttons, template, etc.
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
  protected static $otherAttributeNames = array('base_form', 'objs', 'data');

  public function __construct($args = null) {
    //pkdebug("Making a collection/FormSet, with args:", $args);
    $this->scrolling = true;
    $this->forms = new PartialSet();
    parent::__construct($args);

  }

}
