<?php
namespace Kuga\Core\Api;
use Kuga\Core\Base\ServiceException;
class Exception extends ServiceException{

    public function __construct($msg = '', $code = 0, Exception $previous = null) {
        $all = self::getAllExceptions();
        $msgExt  = $msg;
        $codeExt = $code;
        if(array_key_exists($msg, $all) && is_numeric($msg) && $code===0){
            $msgExt  = $all[$msg];
            $codeExt = $msg;
        }
        parent::__construct($msgExt,$codeExt,$previous);
    }
}