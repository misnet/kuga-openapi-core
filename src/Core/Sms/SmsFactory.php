<?php
namespace Kuga\Core\Sms;
use Kuga\Core\Base\ServiceException;

class  SmsFactory{
    /**
     *
     * @param $config
     * @param string $adapterName
     * @param null $di
     * @return \Kuga\Service\Sms\SmsInterface
     * @throws Exception
     */
    public static function getAdapter($adapterName='aliyun',$configFile,$di=null){
//        $loader = new \Phalcon\Loader();
//        $loader->registerNamespaces(array(
//            'Kuga\Service\Sms'=>'./Sms'
//        ))->register();
        $adapterName = ucfirst(strval($adapterName));
        $className = '\\Kuga\\Core\\Sms\\Adapter\\'.$adapterName;
        if('Aliyun'==$adapterName||'Tencent'==$adapterName){
            return new $className($configFile,$di);
        }else{
            throw new ServiceException('sms adapter '.$adapterName.' not exists');
        }
    }
}