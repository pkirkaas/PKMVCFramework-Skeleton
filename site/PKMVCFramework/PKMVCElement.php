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
 * Right now, basically an empty class -- placeholder for when it gets more
 * implemented
 */
class BaseElement {
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
   * @param Array $ags: Ass array of key/value pairs. Just initializes values
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
   * @return String: HTML representing control
   */
  public function buildHtml() {

  }
  
}

class BaseDbElement extends BaseElement {

}
