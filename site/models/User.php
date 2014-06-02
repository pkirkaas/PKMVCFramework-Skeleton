<?php
/* 
 * The example User class; extends PKMVC\BaseUser
 * 
 * 
 */

use PKMVC\BaseUser;

class User extends BaseUser {
  static $memberDirects = array('email', 'fname', 'lname'); #Will be added to base directs
  protected $email;
  protected $fname;
  protected $lname;
}
