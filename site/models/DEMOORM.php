<?php

/*
 * Library for Magic of Healthy Living Nutritional Calculator
 * DTSS DMA Disney
 * @Author: Paul Kirkaas
 * Author URI: http://devcentral.disney.com/products/digital-media-agency-dma/
 * Version: 1.0
 * 28 April 2014
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

class Demo extends BaseModel {

  public static $memberObjects = array('category'=>'Category','region'=>'Region');
  public static $memberCollections = array(
      'cells'=>
           array('classname'=>'ChartCell','foreignkey'=>'chart_id'),
      'foodgroupconsiderations'=>
           array('classname'=>'FoodGroupConsideration', 'foreignkey'=>'chart_id'),
      'requirements'=>
           array('classname'=>'Requirement', 'foreignkey'=>'chart_id'),
      );
  public static $memberDirects = array('category_id', 'region_id',
    'description', 'other_considerations',);
  public $cells = array(); #Array of chart cells with rules

}
