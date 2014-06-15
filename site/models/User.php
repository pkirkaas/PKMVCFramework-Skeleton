<?php
/* 
 * The example User class; extends PKMVC\BaseUser
 * 
 * 
 */

use PKMVC\BaseUser;
use PKMVC\BaseModel;

class User extends BaseUser {
  protected static $memberDirects = array( 'email', 'fname', 'lname',);
  /*
  static $memberDirects = array(
      'email'=>array('dbtype'=>'varchar', 'eltype'=>'text'), 
      'fname'=>array('dbtype'=>'varchar', 'eltype' => 'text',),
      'lname'=>array('dbtype'=>'varchar', 'eltype'=>'text',)
      ); #Will be added to base directs
      */
  protected static $memberCollections = array(
      'profiles'=>array('classname'=>'Profile', 'foreignkey'=>'user_id')); #Array of names of object collections
  #directs
  protected $email;
  protected $fname;
  protected $lname;

  #collections 
  protected $profiles;
}

/**
 * A user may have several profiles....
 */
class Profile extends BaseModel {
  static $memberDirects = array(
    'name', 'user_id', 'prof_description', 'prof_display_name', 'prof_name',
      'aboutme', 'avatar_link', 'obf_link',
    );
  protected static $memberCollections = array(
      'profile_jobs'=>array('classname'=>'Profile', 'foreignkey'=>'user_id')); #Array of names of object collections
  /*
  protected static $memberDirects = array(
      'name'=>array('dbtype'=>'varchar', 'eltype'=>'text',),
      #figure this out later....
      'user_id'=>array('dbtype'=>'int', 'eltype'=>'',),
  );
      */
  protected $user_id; 
  protected $name; #profile name

  protected $profile_description = null;
  public $profile_display_name = null;
  public $profile_name = null;
  public $aboutme = null;
  public $avatar_link = null;
  protected $obf_link = null;

  #collections of related tables
  public $profile_jobs = null;


}

class ProfileJob extends BaseModel{
  protected static $memberDirects = array('id', 'prof_id', 'description',
       'start', 'end', 'title', 'employer' ); #Array of Class attributes that map directly to table fields.
  public $id = null;
  public $prof_id = null;
  public $start = null;
  public $end = null;
  public $title = null;
  public $description = null;
  public $employer = null;


}