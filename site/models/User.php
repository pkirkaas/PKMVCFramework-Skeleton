<?php
/* 
 * The example User class; extends PKMVC\BaseUser
 * 
 * 
 */

use PKMVC\BaseUser;
use PKMVC\BaseModel;

class User extends BaseUser {
  static $memberDirects = array(
      'email'=>array('dbtype'=>'varchar', 'eltype'=>'text'), 
      'fname'=>array('dbtype'=>'varchar', 'eltype' => 'text',),
      'lname'=>array('dbtype'=>'varchar', 'eltype'=>'text',)
      ); #Will be added to base directs
  #public /*protected*/ static $memberCollections = array('profiles'=>array('classname'=>'Profile', 'foreignkey'=>'user_id')); #Array of names of object collections
  protected $email;
  protected $fname;
  protected $lname;
  #protected $profiles;
}

/**
 * A user may have several profiles....
 */
class Profile extends BaseModel {
  static $memberDirects = array(
      'name'=>array('dbtype'=>'varchar', 'eltype'=>'text',),
      #figure this out later....
      'user_id'=>array('dbtype'=>'int', 'eltype'=>'',),
  );
  protected $user_id; 
  protected $name; #profile name

  

}