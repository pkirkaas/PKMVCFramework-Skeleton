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
 * AppController extends the generic PKMVC BaseController, but adds specialized
 * code that every controller in our app will want, (like, setting a generic
 * app menu, which of course can be overriden...
 * So all other controllers in
 * this app extend this AppController.
 *
 * @author Paul
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

class AppController extends BaseController {
  public function __contstruct($args = null) {
    parent::__construct($args);
    #Get config and build basic menu...
    $menu = ApplicationBase::exec('systempartial', 'basemenu');
    static::setSlot('top-menu', $menu);
  }

  public function preExecute($args = null) {
    $menu = ApplicationBase::exec('systempartial', 'basemenu');
    static::setSlot('top-menu', $menu);
  }
}
