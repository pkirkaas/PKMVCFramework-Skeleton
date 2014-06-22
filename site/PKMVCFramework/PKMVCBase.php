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
 * The absolute PKMVC Base Class that all other PKMVC Components extend. This
 * is to provide some common utility methods to all classes. For example, the
 * feature to combine static arrays up the ancestor hierarchy - getArraysMerged
 *
 * @author Paul
 */
namespace PKMVC;
class PKMVCBase {
  /**
   *
   * @var array: All standard HTML attribute names
   */
  public static $globalHtmlAttributes= array (
      'accesskey', 'class', 'contenteditable', 'contextmenu', 'dir',
      'draggable', 'dropzone', 'hidden', 'id', 'lang', 'spellcheck',
     'style', 'tabindex', 'title', 'translate',
  );

  /*
  public function __construct($args=null) {
  }
   * 
   */









  
  /**
   * Recurse up through inheretence hierarchy and merge static arrays of
   * the given arrayName name. This is for use by ::getMemberDirects(),
   * ::getMemberObjects, ::getMemberCollections()... to support deep object/class
   * inheritence. For example, BaseModel has "memberDirects=array('id');".
   * Child class "BaseUser extends BaseModel" has "memberDirects=array('uname')"
   * Your child user class might be "MyUser extends BaseUser", and 
   * MyUser::memberDirects = array('myextrastuff');
   *
   * So MyUser::getMemberDirects() should return "array('id','uname','myextrastuff');
   * etc.
   *
   * This static function is used by the various "getMemberXXX()" functions, and
   * returns a merged array, with child definitions overriding base defs.
   * @param String class: The calling class
   * @param $arrayName String: the name of the attribute: memberDirects,
   *  memberObjects, or memberCollections (for now)
   * @param $idx Boolean: is the array type indexed or associative? Used for 
   *   merging strategy - $memberDirects are indexed, others assoc.
   * @return Array: Merged array of hierarchy
   */

  protected static function getAncestorArraysMerged($arrayName, $idx = false) {
     #First, build array of arrays....
     $retArr = array();
     $class = get_called_class();
     $retArr[]=$class::$$arrayName; #Deliberate double $$
     while ($par = get_parent_class($class)) {
       if(! property_exists($par, $arrayName)) {
         break;
       }
      $retArr[]=$par::$$arrayName;
      $class=$par;
    }
    #Now merge. Reverse order so child settings override ancestors...
    $retArr = array_reverse($retArr);
    $mgArr = call_user_func_array('array_merge',$retArr);
    //if ($idx) { #Indexed array, return only unique values. For 'memberDirects'
      #Mainly to save the developer who respecifies 'id' in the derived direct
     // $mgArr = array_unique($mgArr);
    //}
    return $mgArr;
  }

  /** Like merging arrays, above, but makes an array from scalar values
   * 
   * @param type $propertyName
   * @param type $idx
   * @return Associate Array of classNames as keys to the property value;
   */
  protected static function getAncestorPropertiesMerged($propertyName) {
     #First, build array of arrays....
     $retArr = array();
     $class = get_called_class();
     $retArr[$class]=$class::$$propertyName; #Deliberate double $$
     while ($par = get_parent_class($class)) {
       if(! property_exists($par, $propertyName)) {
         break;
       }
      $retArr[$par]=$par::$$propertyName;
      $class=$par;
    }
    return $retArr;
  }

  /**
   * Returns an array of all declared classes derived from the base class
   * Of course, doesn't find autoload classes that haven't been loaded yet...
   * @param String|Class|Object|NULL $baseClass: class name, interface, 
   * instance, or NULL for calling class
   * @return Array: All derived classes
   */
  public static function getAllDerivedClasses ($baseClass = null) {
    #Get the class name if $baseClass an instance instead of a class name
    if (is_object($baseClass)) {
      $baseClass = get_class($baseClass);
    } else if ($baseClass == null) {
      $baseClass = get_called_class();
    }
    $classes = get_declared_classes();
    $all = $classes;
    //pkdebug("All Declared Classes?", $classes);
//    $interfaces = get_declared_interfaces();
 //   $all = array_merge($classes, $interfaces);
      //pkdebug("BASE: [$baseClass]; All Declared All?", $all);
    $retArr = array();
    //foreach ($all as $className) {
    foreach ($all as $className) {
      if (is_subclass_of($className, $baseClass)) {
       // pkdebug("WOW!! We got [$className] instanceof [$baseClass]");
        $retArr[] = $className;
      }
    }
    return $retArr;
  }

  /**
   * Checks if this static variable name is defined/declared
   * IN THIS CLASS! Not if inhereted...
   * Works for both private & static vars
   * @param String $varName: Name of the static var
   * @return boolean: 
   */
  public static function hasOwnVar($varName) {
    $myClass = get_called_class();
    $reflector = new \ReflectionClass($myClass);
    $properties = $reflector->getProperties();
    foreach ($properties as $property) {
      if (($property->name==$varName) && ($property->class == $myClass)) {
        return true;
      }
    }
    return false;
  }


  /** General array utility to traverse down an array of keys to end, then
   * set value. Like, to be able to set: $arr['spart']['utask'][5] = "Hello";
   * when $arr['spart'] doesn't even exist yet.
   * 
   * Really just a function, no reason to be an object method.
   * @param array & $fillArr: The REFERENCE to the array to be filled
   * @param array $keys
   * @param type $val
   */
  #Ah, well, an elegant solution to a problem that doesn't exist!
  /*
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
  */



  /** Deep object copy. Can do something more clever with __clone() at some
   * point, but for now...
   * @return static copy of $this
   */
  public function copy() {
    return unserialize(serialize($this));
  }
}
