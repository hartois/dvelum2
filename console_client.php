<?php
/*
 * DVelum console application
 * Return codes
 * 0 - Good
 * 1 - Empty URI
 * 2 - Wrong URI
 * 3 - Application Error
 */
if (isset($_SERVER['argc']) && $_SERVER['argc'] < 2 ){
    exit(1);
}

$scriptStart = microtime(true);

$dvelumRoot =  str_replace('\\', '/' , __DIR__);
// should be without last slash
if ($dvelumRoot[strlen($dvelumRoot) - 1] == '/')
    $dvelumRoot = substr($dvelumRoot, 0, -1);

define('DVELUM', true);
define('DVELUM_ROOT' ,$dvelumRoot);
define('DVELUM_CONSOLE', true);
define('DVELUM_WWW_PATH', DVELUM_ROOT . '/www/');

chdir(DVELUM_ROOT);

//===== loading kernel =========
/*
 * Including initial config
 */
$bootCfg = include DVELUM_ROOT . '/application/configs/dist/init.php';
/*
 * Register composer autoload
 */
require DVELUM_ROOT . '/vendor/autoload.php';
/*
 * Including Autoloader class
 */
require DVELUM_ROOT . '/dvelum2/Dvelum/Autoload.php';
$autoloader = new \Dvelum\Autoload($bootCfg['autoloader']);

use \Dvelum\Config\Factory as ConfigFactory;
use \Dvelum\Config;
use \Dvelum\Request;

$configStorage = ConfigFactory::storage();
$configStorage->setConfig($bootCfg['config_storage']);

//==== Loading system ===========
/*
 * Reload storage options from local system
 */
$configStorage->setConfig(Config::storage()->get('config_storage.php')->__toArray());
/*
 * Connecting main configuration file
 */
$config = Config::storage()->get('main.php');
$_SERVER['DOCUMENT_ROOT'] = $config->get('wwwpath');

/*
 * Disable op caching for development mode
 */
if($config->get('development')){
    ini_set('opcache.enable', 0);
}
/*
 * Setting autoloader config
 */
$autoloaderCfg = Config::storage()->get('autoloader.php')->__toArray();
$autoloaderCfg['debug'] = $config->get('development');

if(!isset($autoloaderCfg['useMap']))
    $autoloaderCfg['useMap'] = true;

if($autoloaderCfg['useMap'] && $autoloaderCfg['map'])
    $autoloaderCfg['map'] = require Config::storage()->getPath($autoloaderCfg['map']);
else
    $autoloaderCfg['map'] = false;

$autoloader->setConfig($autoloaderCfg);

/*
 * Starting the application
 */
$appClass = $config->get('application');
if(!class_exists($appClass))
    throw new Exception('Application class '.$appClass.' does not exist! Check config "application" option!');

$app = new $appClass($config);
$app->setAutoloader($autoloader);
$app->init();

$request = Request::factory();
$request->setUri($_SERVER['argv'][1]);

$app->run();