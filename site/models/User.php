<?php
/* 
 * The example User class; extends PKMVC\BaseUser
 * 
 * 
 */

use PKMVC\BaseUser;
use PKMVC\BaseModel;

class User extends BaseUser {
  protected static $memberDirects = array( 
      'email'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
      'fname'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
      'lname'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
      );
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
    'name'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
    'user_id',
    'prof_description'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
    'prof_display_name'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
    'prof_name'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
    'aboutme'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
    'avatar_link'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
    'obf_link'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
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
  protected static $memberDirects = array(
      'profile_id' => array('key' =>true,),
      'description'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
      'start'=> array('dbtype'=>'date',  'eltype'=>'date'),
      'end'=> array('dbtype'=>'date',  'eltype'=>'date'),
      'title'=> array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
      'employer' => array('dbtype'=>'varchar', 'collength'=>999, 'eltype'=>'text'),
      
      ); #Array of Class attributes that map directly to table fields.
  public $prof_id = null;
  public $start = null;
  public $end = null;
  public $title = null;
  public $description = null;
  public $employer = null;


}