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
 * TODO: Figure out how to integrate this with repeating
 * subforms/collections...
 */

class BaseForm {
  protected $class;

  /**
   *
   * @var String: The template to use with the particular form class
   */
  protected $template = '';

  /**
   * @var String: The various form attributes and their defaults...
   */
  protected $action = '';
  protected $enctype = 'multipart/form-data';
  protected $method = 'POST';
  protected $name = '';
  protected $class = '';
  protected $id = '';

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
   * @return type array of saved objects
   */
  public function submitToClass(Array $formData) {
    $results = array();
    $formData = htmlclean($formData);
    $classNames = array_keys($formData); 
    foreach ($classNames as $className) {
      $obj = $className::get($formData[$className]);
      $obj->save();
      $results[]= $obj;
    }
    return $results;
  }

  /**
   * Return the open form string, based on attributes
   */
  public function openForm() {
    $formTag = "\n<form class='{$this->class}' method='{$this->method}'
      action='{$this->action}' id={$this->id}' enctype='{$this->enctype}' 
        name='{$this->name}' >\n";
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
   * @param String|Array $key: Either the string key name, or an array
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
      $this->elements[$skey] = $sval;
    }
    return $this->elements;
  }

  /** Returns an input element by name in assoc array
   * 
   * @param String $name
   */
  public function getElement($name) {
    if (isset($this->elements[$name])) {
      return $this->elements[$name];
    } else {
      return null;
    }
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
