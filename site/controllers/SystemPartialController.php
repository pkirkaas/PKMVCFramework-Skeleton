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
 * SystemPartialController -- builds system partials, like maybe menus
 * 
 * Many ways to implement/approach, we do the logic here to trim menu items
 * and let the template display all it is given..
 *
 * @author Paul
 */

use PKMVC\BaseController;
use PKMVC\Config;
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

class SystemPartialController extends BaseController {
  public function basemenuPartial() {
    $this->setTemplate('default-menu');
    $config = Config::getConfig();
    $default_menu = $config['menus']['default_menu'];
    $showItems = static::filterMenuItems($default_menu);
    $data = array('menu_items'=>$showItems);
    return $data;
  }


  /**
   * 
   * @param array $menuItems: Array of potential menu items with info to determine
   * if they should be shown or not (like, don't show "Login" item if already
   * logged in). 
   * @return array: Only the items to be shown
   */
  public function filterMenuItems($menuItems) {
     $loggedIn = BaseUser::getCurrent();
     $retItems = array();
     foreach ($menuItems as $menuItem) {
       if ($menuItem['logtype'] == 0) { //For all (like, 'About', so add
         $retItems[] = $menuItem;
         continue;
       }
       if (($menuItem['logtype'] == -1) && !$loggedIn) {#Show not logged in items
         $retItems[] = $menuItem;
         continue;
       }
       if (($menuItem['logtype'] == 1) && $loggedIn) {#Show  logged in items
         $retItems[] = $menuItem;
         continue;
       }
       throw new \Exception("Shouldn't be here...");
     }
     return $retItems;
  }

}
