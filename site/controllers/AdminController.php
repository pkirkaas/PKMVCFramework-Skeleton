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
  public function formarrayAction() {
    // Use var_export()
    $data = array();
    $formarray = array();

    $base = $_SERVER['DOCUMENT_ROOT'];
    $formBase = $base.'/forms/FormArrays.php';
    include ($formBase);
    //var_export();

    $data['form'] = $formarray;
    return $data;

  }
}
