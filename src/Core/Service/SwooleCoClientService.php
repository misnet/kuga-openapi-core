<?php
namespace Kuga\Core\Service;
/**
 * Swoole协程客户端
 *
 * Class SwooleCoClientService
 * @package Kuga\Core\Service
 */
class SwooleCoClientService extends \Kuga\Core\Base\AbstractService{
    private $option;
    public function send($data,$requireReturnData=false)
    {
        $option = $this->_getOption();
        $client = new \Swoole\Client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);

        if ($client->connect($option['host'], $option['port'], $option['connectTimeout'])) {
            $client->send(json_encode($data));
            if($requireReturnData){
                return $client->recv();
            }
        }else{
            $client->close();
            throw new \Exception($this->translator->_('Swoole Server Connection failure'));
        }
        $client->close();
    }
    private function _getOption(){
        $config = $this->_di->getShared('config');
        if (!file_exists($config->swoole)) {
            return false;
        }
        $swooleConfigContent = file_get_contents($config->swoole);
        return \json_decode($swooleConfigContent, true);
    }
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }
}
