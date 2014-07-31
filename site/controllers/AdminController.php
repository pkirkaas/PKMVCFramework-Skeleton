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
 * Description of AdminController
 *
 * @author Paul
 */
use PKMVC\BaseController;
use PKMVC\LayoutController;
use PKMVC\BaseElement;
use PKMVC\BaseDbElement;
use PKMVC\BaseForm;
use PKMVC\BaseUser;
use PKMVC\BaseModel;
use PKMVC\RenderResult;
use PKMVC\PartialSet;
use PKMVC\ApplicationBase;
use PKMVC\Application;
use PKMVC\ControllerWrapper;
use PKMVC\ViewRenderer;

class  AdminController extends AppController {
  public function indexAction() {
    $data = array('val'=>'This is from the Admin/Index Action');
    return $data;
  }
  
  public function sqlAction() {
    $sqlStr = BaseModel::getSqlAll();
    $sqlStrMod  = BaseModel::getSqlAll(true);
    $data=array();
    $data['sql'] = $sqlStr;
    $data['sqlMod'] = $sqlStrMod;
    return $data;
  }

  /**
   * Creates/Edits PHP arrays to define forms. 
   * TODO: Make way more configurable, esp wrt form file names & location 
   * @return array
   */
  public function formarrayAction() {
    // Use var_export()
    $formfile = $_SERVER['DOCUMENT_ROOT'].'/forms/FormArrays.php';
    #TODO++!! Make safe and reasonable!
    #TODO++!! Yes, I mean REALLY. Hate this for now. But the form array is $formarray
    include ($formfile);
    $data = array();
    if (!isset($formarray)) {
      $formarray = array();
    }

    $base = $_SERVER['DOCUMENT_ROOT'];
    $formBase = $base.'/forms/FormArrays.php';
    include ($formBase);
    //var_export();

    $data['form'] = $formarray;
    return $data;

  }


  public function getFormOptions() {
    $formOptions = array();
    $inputs = BaseElement::getValidInputs();
    $inputAttributes = '';
    
  }
}
