<?php

namespace Kuga\Core\Service;

use Kuga\Core\Base\ServiceException;

class PluginManageService
{

    private static $_eventType = 'qing';

    private static $_pluginList = [];

    protected static $eventManager;

    protected static $di;

    /**
     * @param \Phalcon\Events\ManagerInterface $evm
     * @param                                  $di
     *
     * @throws Exception
     */
    public static function init(\Phalcon\Events\ManagerInterface $evm, $di)
    {
        if ( ! $evm instanceof \Phalcon\Events\Manager) {
            throw new ServiceException('PluginManager load error parameter');
        }
        self::$eventManager = $evm;
        self::$di           = $di;
    }

    public static function loadPlugins()
    {
        //register plugin namespace
//        $loader = new \Phalcon\Loader();
//        $loader->registerNamespaces(
//            ['Kuga\Plugin' => QING_CLASS_PATH.'/plugin']
//        )->register();
        //attach plugin
        $plugins = self::getPlugins();
        foreach ($plugins as $plugin) {
            $className = '\Kuga\Core\\'.$plugin;
            if (class_exists($className)) {
                self::$eventManager->attach(self::$_eventType, new $className(self::$di));
            }
        }
    }

    private static function getPlugins()
    {
        self::$_pluginList = ['ExceptionNoticePlugin'];

        return self::$_pluginList;
    }

}