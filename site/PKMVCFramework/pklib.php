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
 * General, non-symfony utility functions
 * Paul Kirkaas, 29 November 2012
 */
function unCamelCase($string) {
  if (!is_string($string) || !strlen($string)) {
    throw new \Exception("No string argument to unCamelCase");
  }
  $str = strtolower(preg_replace("/([A-Z])/", "_$1", $string));
  if ($str[0] == '_') {
    $str = substr($str, 1);
  }
  return $str;
}

function toCamelCase($str, $capitalise_first_char = false) {
  if (!is_string($str))
    return '';
  if ($capitalise_first_char) {
    $str[0] = strtoupper($str[0]);
  }
  $func = create_function('$c', 'return strtoupper($c[1]);');
  return preg_replace_callback('/_([a-z])/', $func, $str);
}

/**
 * For any number of arguments, print out the file & line number,
 * the argument type, and contents/value of the arg -- unless the very last
 * argument is a boolean false.
 */
function pkecho() {
  $args = func_get_args();
  $out = call_user_func_array("pkdebug_base", $args);
  echo "<pre>$out</pre>";
}

function tmpdbg() {
  $args = func_get_args();
  PKMVC\BaseController::addSlot('debug', call_user_func_array("pkdebug_base", $args));
}

function pkdebug() {
  $args = func_get_args();
  $out = call_user_func_array("pkdebug_base", $args);
  pkdebugOut($out);
}

/**
 * Returns the (unlimited) args as a string, arg strings as strings,
 * array and obj args as var_dumps.
 * @return string
 */
function pkdebug_base() {
  //if (sfConfig::get('release_content_env') != 'dev') return;
  $stack = debug_backtrace();
  $stacksize = sizeof($stack);
  //$frame = $stack[0];
  //$frame = $stack[1];
  //$out = "\n".date('j-M-y; H:i:s').': '.$frame['file'].": ".$frame['function'].': '.$frame['line'].": \n  ";
  $out = "\nPKDEBUG OUT: STACKSIZE: $stacksize\n";
  /*
    if (!isset($frame['file']) || !isset($frame['line'])) {
    $out.="\n\nStack Frame; no 'file': ";
    foreach ($frame as $key=>$val) {
    if (is_array($val)) $val = '(array)';
    $out .="[$key]=>[$val] - ";
    }
    $out .= "\n\n";
    // var_dump($stack);
    }// else {
   * 
   */
  $idx = 0;
  while ((empty($stack[$idx]['file']) || ($stack[$idx]['file'] == __FILE__))) {
    $idx++;
  }
  $frame = $stack[$idx];

  $frame['function'] = isset($stack[$idx+1]['function'])?$stack[$idx+1]['function']:'';
  //$out .= pkstack() . "\n\n";
  if (isset($stack[1])) {
    $out .= "\nFrame $idx: " . date('j-M-y; H:i:s') . ': ' . $frame['file'] . ": " . $frame['function'] . ': ' . $frame['line'] . ": \n  ";
  } else {
    $out .= "\n" . date('j-M-y; H:i:s') . ': ' . $frame['file'] . ": TOP-LEVEL: " . $frame['line'] . ": \n  ";
  }
  //}
  $lastarg = func_get_arg(func_num_args() - 1);
  $dumpobjs = true;
  if (is_bool($lastarg) && ($lastarg === false))
    $dumpobjs = false;
  $msgs = func_get_args();
  foreach ($msgs as $msg) {
    $printMsg = true;
    $type = typeOf($msg);
    if ($msg instanceOf sfOutputEscaperArrayDecorator) {
      $printMsg = false;
      //if ($msg instanceOf Doctrine_Locator_Injectable) {
    } else if (is_object($msg)) {
      $printMsg = $dumpobjs;
//        $msg=$msg->toArray(); 
    }
    if ($msg instanceOf Doctrine_Pager)
      $printMsg = false;
    //if ($printMsg && (is_object($msg) || is_array($msg))) $msg = json_encode($msg);
    if ($printMsg && (is_object($msg) || is_array($msg)))
      $msg = pkvardump($msg);
    $out .= ('Type: ' . $type . ($printMsg ? ': Payload: ' . $msg : '') . "\n  ");
  }
  $out.="\nEND DEBUG OUT\n\n";
  return $out;
}

/**
 * Outputs the stack to the debug out.
 * @param type $depth
 */
function pkstack($depth = 10) {
  $out = pkstack_base($depth);
  pkdebugOut($out);
}

/**
 * Returns the stack as a string
 * @param type $depth
 * @return string
 */
function pkstack_base($depth = 10, $args=false) {
  //if (sfConfig::get('release_content_env') != 'dev') return;
  $stack = debug_backtrace();
  $stacksize = sizeof($stack);
  if (!$depth) {
    $depth = $stacksize;
  }
  $frame = $stack[0];
  $out = "STACKTRACE: ".date('j-M-y; H:i:s').": Stack Depth: $stacksize; backtrace depth: $depth\n";
  //pkdebugOut($out);
  ////pkdebugOut("Stack Depth: $stacksize; backtrace depth: $depth\n");
  $i = 0;
  foreach ($stack as $frame) {
    //$out = $frame['file'].": ".$frame['line'].": Function: ".$frame['function']." \n  ";
    if (isset($frame['file']) && ($frame['file'] == __FILE__)) {
      $i++;
      continue;
    }
    //$out .= pkvardump($frame) . "\n";
    if (!empty($frame['file'])) $out.=$frame['file'].': ';
    if (!empty($frame['line'])) $out.=$frame['line'].': ';
    if (!empty($frame['function'])) $out.=$frame['function'].': ';
    if (!empty($frame['class'])) $out.=$frame['class'].': ';
    $out.="\n";
    if ($args) $out .= "Args:" . pkvardump($frame['args']) . "\n";
    //$out .= "Args:" . print_r($frame['args'],true) . "\n";
    if (++$i >= $depth) {
      break;
    }
  }
  return $out;
}

function typeOf($var) {
//  if (sfConfig::get('release_content_env') != 'dev') return;
  if (is_object($var))
    return get_class($var);
  return gettype($var);
}

function ancestry($object) { //Can be object instance or classname
  $parent = $object;
  $parents = array();
  if (is_object($object))
    $parents[] = get_class($object);
  while ($parent = get_parent_class($parent))
    $parents[] = $parent;
  return $parents;
}

/** For debug functions that just echo to the screen --
 *  catch in a string and return.
 * @param type $runnable
 * @return type
 */
function pkcatchecho ($runnable) {
  if (!is_callable($runnable)) {
    return "In pkcatchecho -- the function passed[".
            pkvardump($runnable). "]is not callable...";
  }
  $args = func_get_args();
  array_shift($args);
  ob_start();
  call_user_func_array($runnable, $args);
  //Var_Dump($arg);
  //print_r($arg);
  $vardump = ob_get_contents();
  ob_end_clean();
  ini_set('xdebug.overload_var_dump', 1);
  return "<pre>$vardump</pre>";
}

function pkvardump($arg, $disableXdebug = true) {
  if ($disableXdebug) {
    ini_set('xdebug.overload_var_dump', 0);
  }
  ob_start();
  //Var_Dump($arg);
  print_r($arg);
  $vardump = ob_get_contents();
  ob_end_clean();
  ini_set('xdebug.overload_var_dump', 1);
  return $vardump;
}

function appLogPath($path = null) {
  $defaultPath = $_SERVER['DOCUMENT_ROOT'] . '/logs/app.log'; 
  static $logpath = null;
  if ($path === false) {
    $logpath = $defaultPath;
    return $logpath;
  }
  if (!$path) {
    if (!$logpath) {
      $logpath = $defaultPath;
    }
    return $logpath;
  }
  $logpath = $path;
  return $logpath;
}

//Outputs to the destination specified by $useDebugLog
function pkdebugOut($str) {
  if (true) {
  //  try {
      //$logpath = $_SERVER['DOCUMENT_ROOT'].'/../app/logs/app.log';
      //$logpath =  WP_CONTENT_DIR.'/app.log';
      //$logpath = $_SERVER['DOCUMENT_ROOT'] . '/logs/app.log';
      $logpath = appLogPath();
      $fp = fopen($logpath, 'a+');
      if (!$fp)
        throw new Exception("Failed to open DebugLog [$logpath] for writing");
      fwrite($fp, $str);
      fclose($fp);
   // } catch (Exception $e) {
    //  error_log("Error Writing to Debug Log: " . $e);
     // return false;
    //}
  } else {
    error_log($str);
  }
  return true;
}

function getHtmlTagWhitelist() {
  static $whitelist = "<address><a><abbr><acronym><area><article><aside><b><big><blockquote><br><caption><cite><code><col><del><dd><details><div><dl><dt><em><figure><figcaption><font><footer><h1><h2><h3><h4><h5><h6><header><hgroup><hr><i><img><ins><kbd><label><legend><li><map><menu><nav><p><pre><q><s><span><section><small><strike><strong><sub><summary><sup><table><tbody><td><textarea><tfoot><th><thead><title><tr><tt><u><ul><ol><p>";
  return $whitelist;
}

/** Takes a string or multi-dimentional array of text (like from a POST)
 * and recursively trims it and strips tags except from a whitelist
 * @param type $input input string or array.
 */
function htmlclean ($arr, $usehtmlspecchars = false) {
  $whitelist = getHtmlTagWhitelist();
  if (!$arr) return $arr;
  if (is_string($arr) || is_numeric($arr)) {
    return strip_tags(trim($arr),$whitelist);
  }
  if (is_object($arr)) {
    $arr = get_object_vars($arr);
  }
  if (!is_array($arr)) {
    pkdebug("Bad Data Input?:", $arr);
    throw new Exception ("Unexpected input to htmlclean:".pkvardump($arr));
  }
  $retarr = array();
  foreach ($arr as $key => $value) {
    $retarr[$key] = htmlclean($value);
  }
  return $retarr;
}

/**
 * Removes the protocol, domain, and parameters, just returns
 * indexed array of route segments. ex, for URL:
 * http://www.example.com/some/lengthy/path?with=get1&another=get2
 * ... returns: array('some', 'lengthy', 'path');
 * $param Boolean|String $default: If the first two segments are missing, should
 * we return the default value for them? Default false, otherwise probably 'index'
 * @return Array: Route Segments
 */
function getRouteSegments($default = false) {
  $aseg = $_SERVER['REQUEST_URI'];
  $breakGets = explode('?',$aseg);
  $noGet = $breakGets[0];
  $anarr = explode('/',$noGet);
  array_shift($anarr);
  if ($default) {
    for ($i = 0; $i < 2; $i++) {
      if (!isset($anarr[$i]) || !$anarr[$i]) {
        $anarr[$i] = 'index';
      }
    }
  }
  return $anarr;
}

/**
 * 
 * @return String: The URL without subdirs, but with protocol (http://, etc)
 */
function getBaseUrl() {
  $pageURL = 'http';
  if (!empty($_SERVER["HTTPS"])) {$pageURL .= "s";}
  $pageURL .= "://";
  return $pageURL.$_SERVER["HTTP_HOST"];
}


function getUrl() {
  return getBaseUrl(). $_SERVER["REQUEST_URI"];
}

/**
 * Returns all components of the page URL, without the 
 * GET query parameters
 */
function getUrlNoQuery() {
  $noGetUriArr = explode('?',$_SERVER["REQUEST_URI"]);
  //var_dump("NO GET URI:", $noGetUriArr);
  $noGetUri = $noGetUriArr[0];
  $baseUrl = getBaseUrl();
  //$baseUrl = substr($baseUrl, 0, -1);
  $getUrlNoQuery = $baseUrl.$noGetUri;
  return $getUrlNoQuery;
}

/** Sets (changes or adds or unsets/clears) a get parameter to a value
 *
 * 
 * @param type $getkey -- the get parameter name
 * @param type $getval -- the new get parameter value, or if NULL,
 *   clea's the get parameter
 * @param type $qstr -- can be null, in which case the current URL is
 * used and returned with the GET parameter added, or an empty string '',
 * in which case just a query string is returned, or just a query string,
 * or another URL
 */

function setGet($getkey, $getval = null, $qstr=null) {
  if ($qstr === '') { 
    if ($getval !== null) {
      return http_build_query(array($getkey=>$getval));
    } else {
      return '';
    }
  }
  if ($qstr === null) {
    $qstr = getUrl();
  }
  $start = substr($qstr,0,4);
  $starts = substr($qstr,0,5);
  $col = substr($qstr,6,1);
  $fullurl=false;
  $preqstr = '';
  $qm = false;
  $urlarr = explode('?',$qstr);
  //$returi = '';
  if (strpos($qstr,'?') === false) {# No ?-check if URL or query str
    $qm = false;
  } else {
    $qm = true;
  }
  if ((($start == 'http') || ($starts == 'https')) && ($col = '/')) { //URL
    $fullurl = true;
  }
  
  if (empty($urlarr[0]) || $qm || $fullurl) {
    $preqstr = array_shift($urlarr);
  }
  $quearr = array();
  if (!empty($urlarr[0])) {
     parse_str($urlarr[0], $quearr);
  }
  if ($getval === null) {
    unset($quearr[$getkey]);
  } else {
    $quearr[$getkey] = $getval;
  }
  $retquery = http_build_query($quearr);
  $returl = $preqstr .'?'.$retquery;
  return $returl;
}


/**
 * Creates a select box with the input
 * @param $name - String - The HTML Control Name. Makes class from 'class-$name'
 * #@param $label - String - The label on the control
 * #@param $key_str - The key of the select option array element
 * #@param $val_str - The key for the array element to display in the option
 * @param $arr - Array - The array of key/value pairs
 * @param $selected - String or Null - if present, the selected value
 * @param $none - String or Null - if present, the label to show for a new
 *   entry (value 0), or if null, only allows pre-existing options
 * @return String -- The HTML Select Box
 **/

function makePicker($name,$key,$val,$arr, $selected=null, $none=null) {
#function makePicker($name, $arr, $selected=null, $none=null) {
  $select = "<select name='$name' class='$name-sel'>\n";
  if ($none) $select .= "\n  <option value=''><b>$none</b></option>\n";
  foreach ($arr as $row) {
    $selstr = '';
    if ($selected == $row[$key]) $selstr = " selected='selected' ";
    $option = "\n  <option value='".$row[$key]."' $selstr>".$row[$val]."</option>\n";
    $select .= $option;
  }
  $select .= "\n</select>";
  return $select;
}


/** Performs the equivalent of "filter_input($type = INPUT_REQUEST,...) if that
 * existed.
 * @param type $var
 * @param type $filter
 * @param type $options
 */
function filter_request($var, $filter = FILTER_DEFAULT, $options = null) {
  $res = filter_input(INPUT_GET, $var, $filter, $options);
  if ($res === null) $res=filter_input(INPUT_POST, $var, $filter, $options);
  if ($res === null) $res=filter_input(INPUT_COOKIE, $var, $filter, $options);
  return $res;
}



/** No guarantee, but approximate heuristic to determine if an array is
 * associative or integer indexed.
 * NOTE: Will return FALSE if array is empty, and TRUE if array is 
 * indexed but not sequential. 
 * @param type $array
 * @return type
 */
function isAssoc($array) { return ($array !== array_values($array));}

/**
 * Determines if the value can be output as a string.
 * @param type $value
 * @return boolean: Can the value be output as a string?
 */
function stringable($value) {
    if (is_object($value) and method_exists($value, '__toString')) return true;
    if (is_null($value)) return true;
    return is_scalar($value);
}


/**
 * Takes any number of arguments as scalars or arrays, or nested arrays, and
 * returns a 1 dimentional indexed array of the values 
 * $args: any number of arguments to flatten into an array
 * @return array: 1 dimensional index array of values
 */
function array_flatten(/*$args*/) {
  $args = func_get_args();
  $return = array();
  array_walk_recursive($args, function($a) use (&$return) { $return[] = $a; });
  return $return;
}

/**
 * Converts a compatable argument to an actual integer type ('7' -> 7), or 
 * boolean false ('010' -> false, '' -> false).
 * Totally unnecessary function but more convenient than remembering constant 
 * @param type $arg
 */
function to_int ($arg) {
 $arg = filter_var($arg, FILTER_VALIDATE_INT);
 return $arg;
}

/**
 * Cleans a string for output. Basically, wrap all output in this function, then
 * can change method used.
 * @param string $str: The string to clean
 * @return string: The clean string.
 */
function cln_str($str, $filter = FILTER_SANITIZE_STRING) {
  return filter_var($str, $filter);
}



function cln_arr_val(Array $arr, $key, $filter = FILTER_SANITIZE_STRING) {
  if (!array_key_exists($key, $arr)) {
    return null;
  }
  return cln_str($arr[$key], $filter);
}

/**
 * Encodes a string with HTML special characters -- including single and double
 * quotes - mainly for use to include an aritrary string (including HTML
 * input elements for form templates) in an HTML data-XXX attribute.
 * IDEMPOTENT!!
 * @param String $str: The input string, which may contain special HTML chars.
 * @return String: The HTML Encoded string
 */
function html_encode ($str) {
  return filter_var($str, FILTER_SANITIZE_FULL_SPECIAL_CHARS, ENT_QUOTES);
}

/**
 * Decodes a string previously encoded for HTML special characters --
 * including single and double quotes - mainly for use decoding an HTML data-XXX attribute.
 * @param String $str: The input string, which may contain special HTML chars.
 * @return String: The HTML Encoded string
 */
function html_decode($str) {
   htmlspecialchars_decode($str, ENT_QUOTES);
}

/**
 * Insert the value into the given array (or new array) at the appropriate
 * depth/key sequence specified by the array of keys. Ex: 
 * var_dump(insert_into_array(array('car','ford','mustang','engine'), "351 Cleavland"));
 * Outputs:
 * array (size=1)
  'car' => 
    array (size=1)
      'ford' => 
        array (size=1)
          'mustang' => 
            array (size=1)
              'engine' => string '351 Cleavland' (length=13)
 * @param array $keys: Sequence/depth of keys
 * @param Mixed $value: Whatever value to assign to the location
 * @param array|NULL $arr -- Optional array to add to or create 
 * @return Array: Array with value set at appropriate vector. If called with
 * $retar = &insert_into_array(.... $arr);, $retar will be a reference to $arr
 */
function &insert_into_array(Array $keys, $value,& $arr = null) {
  if ($arr === null) {
    $arr = array();
  }
  $x = & $arr;
  foreach ($keys as $keyval) {
    $x = & $x[$keyval];
  }
  $x = $value;
  return $arr;
}


/**
 * Examines an array and checks if a key sequence exists
 * @param array $keys: Array of key sequence, like
 * array('car','ford','mustang','engine')
 * @param array $arr: The array to examine if key sequence is set, for ex:
 * $arr['car']['ford']['mustang']['engine'] = "351 Cleavland";
 * @return boolean: True if array key chain is set, else false
 */
//function array_key_exists_depth(Array $keys, Array $arr) {
function array_keys_exist(Array $keys,  $arr = null) {
  if (!$arr) return false;
  if (!is_array_accessable($arr)) return false;
  foreach ($keys as $keyval) {
    if (!is_array_accessable($arr) || ! arrayable_key_exists($keyval, $arr)) {
      return false;
    }
    $arr = $arr[$keyval];
  }
  return true;
}

function is_array_accessable($arg) {
  return (is_array($arg) || ($arg instanceOf ArrayAccess));
}

/** Similar to above (array_keys_exist()), only returns the value at the 
 * location
 * @param array $keys
 * @param array $arr
 * @return mixed: The value at the location
 */
function array_keys_value(Array $keys, $arr = null) {
  if (!$arr) return false;
  if (!is_array_accessable($arr)) return false;
  foreach ($keys as $keyval) {
    if (!is_array_accessable($arr) || ! arrayable_key_exists($keyval, $arr)) {
      return false;
    }
    $arr = $arr[$keyval];
  }
  return $arr;
}

/**
 * Like the system "array_key_exists", except for ArrayAccess implementation
 * as well.
 * @param int|str $keyval
 * @param array|ArrayAccess $arr
 * @return boolean: True if key exists, else false
 */
function arrayable_key_exists($keyval, $arr) {
  if (is_array($arr)) return array_key_exists($keyval, $arr);
  if ($arr instanceOf ArrayAccess) return $arr->offsetExists($keyval);
  throw new Exception ("Argument (2) to arrayable_key_exists is not arrayable");
}