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
use PKMVC\BaseUser;
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
   * Register a new user. Both presents the form to be completed, and also 
   * processes the form after submission
   */
  public function registerAction() {
    $form = new BaseForm();

    if (($_SERVER['REQUEST_METHOD'] == 'POST')) { //Save -- form submitted
      $formData = array();
      if (isset($_POST['user'])) {
        $formData['user'] = $_POST['user'];
        $user = $form->submitToClass($formData);
        if (($user instanceOf User) && ($user->getId())) {
          $userId = $user->getId();
        }
      }
    }
    $formElements = array(
        'reg' => array('name'=>'user[reg]', 'type'=>'hidden', 'value'=>'1'),
        'fname' => array('name'=>'user[fname]', 'class'=>'input fname',
            'placeholder' => "First Name"),
        'lname' => array('name'=>'user[lname]', 'class'=>'input lname',
            'placeholder' => "Last Name"),
        'uname' => array('name'=>'user[uname]', 'class'=>'input uname',
            'placeholder' => "Enter User's Name"),
        'password' => array('type' => 'password', 'name'=>'user[cpassword]',
            'class'=>'input password', 'placeholder'=>"Enter a password"),
        'email'=>array('type'=>'email', 'name'=>'user[email]', 'class'=>'input email',
            'placeholder'=>"Email Address"),
        'submit'=>array('type'=>'submit', 'name'=>'user[do_reg]', 'class'=>'input submit',
            'value'=>"Submit To Me!"),
        'button'=>array('type'=>'button', 'name'=>'user[do_reg]', 'class'=>'input submit',
            'value'=>"1", 'content'=>'Complete Registration'),
        'textarea'=>array('type'=>'textarea', 'name'=>'textarea', 'class'=>'input textarea',
            'value'=>"textareavalue", 'content'=>'I am a lengthy piece of text', 'cols'=>'80', 'rows'=>'12'),
    );
    $form->addElement($formElements);
    $data = array();
    $data['form'] = $form;
    $data['sample'] = "My Data from the User controller Register action!";
    return $data;
  }
  public function editAction() {

  }
  public function loginAction() {
    $data = array();
    $msg = "All Good...";
    $form = new BaseForm();
    if (($_SERVER['REQUEST_METHOD'] == 'POST')) { //Save -- form submitted
      $formData = array();
      if (isset($_POST['user'])) {
        $formData['user'] = $_POST['user'];
        $uname = $formData['user']['uname'];
        $cpassword = $formData['user']['cpassword'];
        $user = User::login($uname,$cpassword);
        if ($user instanceOf BaseUser) {
          static::redirect(); #Go Home...
        }
        $msg = "Login Failure!!!";
      } else {
        $msg = "Hmmm ... Didn't even get post data...";
      }
    }
    $formElements = array(
        'uname'=>array('name'=>'user[uname]', 'placeholder'=>'User Name'),
        'cpassword'=>array('name'=>'user[cpassword]', 'type'=>'password',
            'placeholder'=>'Password'),
        'submit'=>array('type'=>'submit', 'name'=>'user[do_reg]', 'class'=>'input submit',
            'value'=>"Submit To Me!"),
        );
    $form->addElement($formElements);
    $data['form'] = $form;
    $data['msg'] = $msg;
    return $data;

  }

  public function logoutAction() {
    $_SESSION = array();
    static::redirect();
  }

  /**
   * Edit User Info and multiple profiles...
   */
  public function profileAction() {
    $user = User::getCurrent();
    if (!$user instanceOf BaseUser) {
      throw new \Exception ("No current user in Profile action");
    }
    $data = array();
    $user = $this->processPost($user);
    $formElements = array(
      'uname'=>array('label'=>'User Name', 'name_segment'=>'uname',
          'placeholder'=>'User Name',),
      'email'=>array('label'=>'Email?', 'name_segment'=>'email',
          'placeholder'=>'Email',),
      'fname'=>array('label'=>'First Name', 'name_segment'=>'fname',
          'placeholder'=>'Your First Name',),
      'lname'=>array('label'=>'Last Name', 'name_segment'=>'lname',
          'placeholder'=>'Your Last Name', ),
      'profiles'=>array('input'=>'formset','subform'=>'profiles',
        'name_segment'=>'profiles', 'class'=> 'doggy',
        'elements'=> array(
          'profile_description'=>array('name_segment'=>'profile_description', 
              'data-stuff'=>'fromUserController', 'label' => 'Prof Desc',
              'placeholder'=>"Describe your profile", ),
          'profile_name'=>array('type'=>'text', 'name_segment'=>'profile_name',
              'data-stuff'=>'fromUserController', 'label'=>'Prof Blah',
              'placeholder'=>"Describe your Blah", )
                  ),
          ),
        );
    $formArgs = array('base_object'=>$user, 'elements'=>$formElements,
        'name_segment'=>'user');
    $form = new BaseForm($formArgs);
    $data['form'] = $form;
    return $data;
  }


  /** Experimenting with new form type/initialization - 
   * 
   * @return \PKMVC\BaseForm
   */
  public function playAction() {
    $els = array('name'=>'user','elements'=>array(
      array('input'=>'html','content'=>"<h2>This is the Form</h2>"),
      array('input'=>'html','content'=>"<div class='el-wrapper'>"),
      'testtext'=>array('label'=>'Label for Text Field',
                        'name'=>'text_data',
                        'value'=>"This is O'Rielly's text stuff"),
      'testtextarea'=>array('label'=>"Label For TextArea Input",
                            'input'=>'textarea', 'name'=>'textaarea_data', 
                            'content'=>"This is text area O'Brien's\nSong"),
        'subform' => array('subform' => array('elements'=>array(
          array('input'=>'html','content'=>"<h2>This is the SubForm</h2>"),

        ))),

      array('input'=>'html','content'=>"</div>"),
    ));
    $form = new BaseForm($els);
    $data['form'] = $form;
    return $data;
  }
}
