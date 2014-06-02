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

define('BASE_DIR',__DIR__);


/*Example Configuration file for PKMVC Framework
 */
use PKMVC\ViewRenderer;
 
#Define DB parameters
define('DB_NAME',	'pkirkaas_demo');
define('DB_SERVER','localhost');
define('DB_USER','root');
define('DB_PASSWORD','mysql');



#Include base PKMVC files, and your custom application files
require_once __DIR__.'/PKMVCFramework/PKMVC-config.php';
require_once __DIR__.'/controllers/AppController.php';
require_once __DIR__.'/controllers/IndexController.php';
require_once __DIR__.'/controllers/UserController.php';
require_once __DIR__.'/controllers/SystemPartialController.php';
require_once __DIR__.'/models/DEMOORM.php';
require_once __DIR__.'/models/User.php';
ViewRenderer::$templateRoot = __DIR__.'/templates';
