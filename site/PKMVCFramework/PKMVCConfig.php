<?php

/**
 * PKMVC Framework 
 * The Configuration class, that manages settings/configuration files
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @link     
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */
/**
 * Description of PKMVCConfig
 *
 * @author Paul
 */

namespace PKMVC;

class Config {

  /** Contains the merged configuration files
   * @var array 
   */
  protected static $config = null;

  /** Merges the given config arg to the application configuration.
   * Can be an array, or a string identifying a config file to include.
   * @param Mixed $config: Array or filename containing a $config array.
   * @return Array: The resultant config array
   */

  /** Default path to scan for user config files
   *
   * @var String - default config path;
   */
  protected static $configPath = 'configs';

  /** Scans the given directory (or default if null) for 
   * any php files, reads them, and merges their config data
   * @param strin $configPath: The directory path to scan for config files,
   * or the path to a single php file.
   */
  public static function loadConfigs($configPath = null) {
    if (is_string($configPath) && $configPath && file_exists($configPath)) {
      static::$configPath = $configPath;
    } else if (is_string(static::$configPath) && static::$configPath &&
            file_exists(static::$configPath)) {
      $configPath = static::$configPath;
    } else {
      throw new \Exception("Cannot find config path: [" .
      static::$configPath . "] OR [$configPath]");
    } #Okay, we have a valid file or dir
    $configFiles = array();
    if (is_file($configPath)) { #Single File
      $configFiles[] = $configPath;
    } else { #Assume dir; get all php files
      $fileNameArr = scandir($configPath);
      foreach ($fileNameArr as $fileName) {
        if (substr($fileName,-4) == '.php') { #PHP File, add to the list
          $configFiles[] = $configPath.'/'.$fileName;
        }
      }
    } #We have an array of config files -- read them
    if (static::$config === null) {
      static::$config = array();
    }
    $resConfig  = static::$config;
    foreach ($configFiles as $configFile) {
      $config = array();
      include ($configFile);
      $resConfig = array_merge_recursive($resConfig, $config);
    }
    static::$config = $resConfig;
    return static::$config;
  }

  public static function addConfig($configArr = array()) {
    if (is_string($configArr)) { #Check if filename exists
    }
    static::$config = array_merge_recursive(static::$config, $configArr);
    return static::$config;
  }

  /**
   * Returns the system config array
   * @return Array: The application configuration array
   */
  public static function getConfig() {
    if (static::$config === null) {
      static::loadConfigs();
    }
    return static::$config;
  }

}
