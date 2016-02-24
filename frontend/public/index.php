<?php

$start = microtime(true);

error_reporting(E_ALL);

use Phalcon\Mvc\View;
use Phalcon\Session\Adapter\Files as Session;
use Phalcon\Logger;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Db\Adapter\Pdo\Mysql as Connection;
use Phalcon\Mvc\Dispatcher;
use \Phalcon\Session\Adapter\Redis as RedisAdapter;

use \Phalcon\Cache\Frontend\Output as OutputFrontend;
use \Phalcon\Cache\Backend\Redis as RedisBackend;

try {
    $loader = new \Phalcon\Loader();
    $loader->registerDirs(array(
        '../app/controllers/',
        '../app/models/',
    	'../app/forms/',
    	'../app/plugins/'/*,
    	'../app/librarys/'*/
    ))->register();

    /*$loader->registerClasses(
    		array(
				"Email"         => "/home/www/rcys/app/librarys/Email.php",
				"PHPExcel"		=> "/home/www/rcys/app/librarys/PHPExcel.php"
    		)
    )->register();*/
    
    $di = new Phalcon\DI\FactoryDefault();
    
    $di->set('dispatcher', function () use ($di) {
    	$eventsManager = new EventsManager;
    	$eventsManager->attach('dispatch:beforeDispatch', new SecurityPlugin);
		
    	$dispatcher = new Dispatcher;
    	$dispatcher->setEventsManager($eventsManager);
    	
    	return $dispatcher;
    });
    
	$di->set('session', function() {
		$session = new Session();
		$session->start();
		return $session;
	});
	
	/*$di->set('viewCache', function () {
		$frontCache = new OutputFrontend( array( "lifetime" => 60 ) );	
		$cache = new RedisBackend( $frontCache, array( 'host' => 'localhost', 'port' => 6379, 'persistent' => true ) );
		return $cache;
	});
    
	$di->set('modelsCache', function () {
			$frontCache = new Phalcon\Cache\Frontend\Data(
					array(
							"lifetime" => 86400
					)
			);
		
			$cache = new RedisBackend(
				$frontCache,
				array(
						'host' => 'localhost',
						'port' => 6379,
						'persistent' => true
				)
		);
		
			return $cache;
		});
	*/
	
    $di->set('flash', function () {
    	$flash = new \Phalcon\Flash\Direct(
    			array(
    					'error'   => 'alert alert-danger',
    					'success' => 'alert alert-success',
    					'notice'  => 'alert alert-info',
    					'warning' => 'alert alert-warning'
    			)
    	);
    
    	return $flash;
    });

    /*$di->set('db', function () {
    	$eventsManager = new EventsManager();
    	
    	$logger = new FileLogger("/home/s_mastihin_by/app/logs/debug.log");
    	
    	$eventsManager->attach('db', function ($event, $connection) use ($logger) {
    		if ($event->getType() == 'beforeQuery') {
    			$logger->log($connection->getSQLStatement(), Logger::DEBUG);
    		}
    	});
    	
    	$connection = new Connection(array(
    			"host" => "localhost",
    			"username" => "root",
    			"password" => "3cBncUjjHUaNVzM7",
    			"dbname" => "oscam",
    			"options" => array(
    					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    			)
    	));
    	
    	$connection->setEventsManager($eventsManager);
    	
    	return $connection;
    });*/
    
    $di->set('view', function () {
        $view = new \Phalcon\Mvc\View();
        $view->setViewsDir('../app/views/');
        /*$view->registerEngines(
        		array(
        				".volt" => 'Phalcon\Mvc\View\Engine\Volt'
        		)
        );*/
        $view->registerEngines(
        		array(
        				".volt" => function ($view, $di) {
        					$volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
        					$volt->setOptions(array('compileAlways' => true));
        					
        					$volt->getCompiler()->addFunction('nl2br', 'nl2br');
        					$volt->getCompiler()->addFunction('md5', 'md5');
        
        					return $volt;
        				}
        		)
        );
        return $view;
    });


    $application = new \Phalcon\Mvc\Application($di);

    echo $application->handle()->getContent();

} catch (\Phalcon\Exception $e) {
     echo "PhalconException: ", $e->getMessage();
     echo "<br>line: " . $e->getLine();
}

$time = microtime(true) - $start;
printf('<div class="text-right" style="color: red; font-size: 8pt;">%.5F ', $time);
echo convert(memory_get_usage()) . '</div>';
function convert($size) {
    $unit = array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

