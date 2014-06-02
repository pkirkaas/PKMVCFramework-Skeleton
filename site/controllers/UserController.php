<?php

/** The Index Controller for the the PKMVC Framework Demo
 *  Paul Kirkaas
 *  30-Apr-14 12:51
 */

use PKMVC\BaseController;
use PKMVC\LayoutController;
use PKMVC\BaseElement;
use PKMVC\BaseDbElement;
use PKMVC\BaseForm;
use PKMVC\BaseModel;
use PKMVC\RenderResult;
use PKMVC\PartialSet;
use PKMVC\ApplicationBase;
use PKMVC\Application;
use PKMVC\ControllerWrapper;
use PKMVC\ViewRenderer;

class UserController extends AppController {

  /**
   * The main index action 
   */
  public function indexAction() {
    $data = array();
    $data['sample'] = "My Data from the User controller Index action!";
    return $data;
  }

  /**
   * Register a new user
   */
  public function registerAction() {
    $form = new BaseForm();
    $formElements = array(
        'uname' => array('name'=>'uname', 'class'=>'input uname',
            'placeholder' => "Enter User's Name"),
        'password' => array('type' => 'password', 'name'=>'password',
            'class'=>'input password', 'placeholder'=>"Enter a password"),
        'email'=>array('type'=>'email', 'name'=>'email', 'class'=>'input email',
            'placeholder'=>"Email Address"),
    );
    $form->addElement($formElements);
    $data = array();
    $data['form'] = $form;
    $data['sample'] = "My Data from the User controller Index action!";
    return $data;
  }
}
