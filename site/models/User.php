<?php
/* 
 * The example User class; extends PKMVC\BaseUser
 * 
 * 
 */

use PKMVC\BaseUser;
use PKMVC\BaseModel;

class User extends BaseUser {
  static $memberDirects = array('email', 'fname', 'lname'); #Will be added to base directs
  protected static $memberCollections = array('profiles'=>array('classname'=>'Profile', 'foreignkey'=>'user_id')); #Array of names of object collections
  protected $email;
  protected $fname;
  protected $lname;
  protected $profiles;
}

/**
 * A user may have several profiles....
 */
class Profile extends BaseModel {
  protected $user_id; 
  protected $name; #profile name

  

}