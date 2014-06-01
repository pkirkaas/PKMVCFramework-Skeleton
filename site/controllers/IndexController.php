<?php

/** The Base Controller & Wrapper
 *  Paul Kirkaas
 *  30-Apr-14 12:51
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

class IndexController extends BaseController {

  /**
   * The main index action 
   */
  public function indexAction() {
    $data = array();
    $data['sample'] = "My Data from the controller!";
    return $data;
  }
}

