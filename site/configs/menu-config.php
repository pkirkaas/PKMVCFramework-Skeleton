<?php

/**
 * PKMVC Framework 
 * Sample menu configuration file, to be read into PKMVC\Config
 * Multiple sources for config will be merged
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @link     
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */

#indicated by 'logtype' key -- -1 for logged out, 0 for all, 1 for only logged in...
/** Menu configuration:
 * Indexed/Sequential array of Menu Items, each of which is a multi-dimenesional associative array of key/value sets:
 * route => array('controllerName','actionName','argN..")
 * label => "Item Label"
 * logtype => 1 or 0 #Is the user logged in?
 * title => "ToolTip"
 */
$default_menu = array();
$default_menu[] = array('route' => array(), 'label' => 'Home', 'logtype' => 0, 'title' => 'Home');
$default_menu[] = array('route' => array('about'), 'label' => 'About', 'logtype' => 0, 'title' => 'About Quantum Profiles');
$default_menu[] = array('route' => array('user','login'), 'label' => 'Login', 'logtype' => -1,'title'=>'Log In');
$default_menu[] = array('route' => array('user','register'), 'label' => 'Register', 'logtype' => -1,'title'=>'New User Registration');
$default_menu[] = array('route' => array('user','profile'), 'label' => 'Profiles', 'logtype' => 1, 'title' => "Manage your QProfiles");
//$default_menu[] = array('route' => array('user','account'), 'label' => 'Account', 'logtype' => 1, 'title' => "Manage your Account settings");
$default_menu[] = array('route' => array('user','edit'), 'label' => 'Edit', 'logtype' => 1, 'title' => "Edit your Profiles");
$default_menu[] = array('route' => array('user','logout'), 'label' => 'Logout', 'logtype' => 1, 'title' => 'Log out of your account');

$config = array();
$config['menus'] = array();
$config['menus']['default_menu'] = $default_menu;