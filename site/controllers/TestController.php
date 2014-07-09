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

class  TestController extends AppController {
  public function indexAction() {
    $data = array();
    $data['form'] = "Hello";
    $user = User::get(6);
    if (!$user instanceOf BaseUser) {
      throw new \Exception ("No current user in Profile action");
    }
    $objarr['user'] = $user;
    /*
    if (is_array_accessable($user)) {
      tmpdbg("User IS Array Accessable");
    } else {
      tmpdbg("User IS NOT !!!!!!  Array Accessable");
    }
     * 
     */
    //tmpdbg("UNAME FROM INDEX:", array_keys_value(array('uname'), $user));
    //return $data;





    //$user = $this->processPost($user);
    $formElements = array(
      'uname'=>array('label'=>'User Name', 'name_segment'=>'uname',
          'placeholder'=>'User Name',),
      'email'=>array('label'=>'Email?', 'name_segment'=>'email',
          'placeholder'=>'Email',),
        /*
      'fname'=>array('label'=>'First Name', 'name_segment'=>'fname',
          'placeholder'=>'Your First Name',),
      'lname'=>array('label'=>'Last Name', 'name_segment'=>'lname',
          'placeholder'=>'Your Last Name', ),
      'profiles'=>array('input'=>'formset','subform'=>'profiles',
        'name_segment'=>'profiles', 'class'=> 'doggy','create_label'=>'Create',
        'elements'=> array(
          'profile_description'=>array('name_segment'=>'profile_description', 
              'data-stuff'=>'fromUserController', 'label' => 'Prof Desc',
              'placeholder'=>"Describe your profile", ),
          'profile_name'=>array('type'=>'text', 'name_segment'=>'profile_name',
              'data-stuff'=>'fromUserController', 'label'=>'Prof Name',
              'placeholder'=>"Profile Name of Blue Blazer", )
                  ),
          ),
         * 
         */
        );
    $formArgs = array('base_object'=>$user, 'elements'=>$formElements,
        'name_segment'=>'user');
    $form = new BaseForm($formArgs);
  $form->bindTo($user);
    tmpdbg("UNAME: [{$objarr['user']['uname']}]"); 
  $data['form'] = $form;
  //BaseController::addSlot('debug',pkdebug_base("The Form is:", $form));
  return $data;
  }
}