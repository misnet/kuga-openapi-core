<?php
namespace Kuga\Core\Service;
use Kuga\Core\Base\ServiceException;

class FileService{
    /**
     * 文件服务
     * @param string $adapter Local或Aliyun
     * @param array $config
     * @param $di \Phalcon\DiInterface 
     * @throws Exception
     * @return \Kuga\Service\File\FileAdapter
     */
    public static function factory($adapter,$config=array(),$di=null){
//        $loader = new \Phalcon\Loader();
//        $loader->registerNamespaces(array(
//            'Kuga\Core\File'=>'./File',
//            'Kuga\Service\File\Adapter'=>'./File/Adapter'
//        ))->register();
        $className = '\Kuga\Core\File\Adapter\\'.ucfirst($adapter);
        if(!$di){
            $di =  \Phalcon\DI::getDefault();
        }
        if(class_exists($className)){
            $service =new $className($di);
            $service->initOption($config);
            return $service;
        }else{
            throw new ServiceException($className.' is not exist.');
        }
    }
    public static function getThumbUrl($src,$width,$height,$option=''){
        $di =  \Phalcon\DI::getDefault();
        if(stripos($src, '/')===0){
            $fs = self::factory('Local',$di->get('config')->localfile);
        }else{
            $fs = $di->getShared('fileStorage');
        }
        return $fs->getVoltThumbUrl($src,$width,$height,$option);
    }
}