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


/** Sample index.php file to start an PKMVC Framework site
*/
use PKMVC\Application;
error_reporting(E_ALL);
ini_set("display_errors", 1);
chdir(dirname(__DIR__)); #Tip from ZF2
require_once (__DIR__.'/../config.php');
$segments = getRouteSegments();
$action = isset($segments[1]) ? $segments[1] : 'index';
$controller = isset($segments[0]) ? $segments[0] : 'index';
#Got Controller & Action, remove and pass on any extra args
array_shift($segments);
array_shift($segments);
$app = new Application();
$app->run($controller, $action, $segments, true);
