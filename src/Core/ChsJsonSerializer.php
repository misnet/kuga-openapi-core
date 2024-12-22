<?php
namespace Kuga\Core;
use Phalcon\Storage\Serializer\SerializerInterface;

class ChsJsonSerializer implements SerializerInterface{
    private $data;
    protected $issuccess = true;
    public function getData():string{
        return $this->data;
    }
    public function serialize():string{
        $d =  \json_encode($this->data,\JSON_UNESCAPED_UNICODE);
        if(\json_last_error() === \JSON_ERROR_NONE){
            $this->issuccess = false;
        }
        return $d;
    }
    public function setData($data):void{
        $this->data = $data;
    }
    public function unserialize( $data){
        $d = \json_decode($data,true);
        if(\json_last_error() === \JSON_ERROR_NONE){
            $this->issuccess = false;
        }
        $this->data = $d;
    }
    public function issuccess():bool{
        return $this->issuccess;
    }
}