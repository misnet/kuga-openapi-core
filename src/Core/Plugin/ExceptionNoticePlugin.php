<?php
namespace Kuga\Plugin;
use Kuga\Core\Base\ErrorObject;
use Kuga\Core\Service\ExceptionWorkerService;

/**
 * Class ExceptionNoticePlugin
 * 异常通知处理类，异步
 *
 * @package Kuga\Plugin
 * @author Donny
 *
 */
class ExceptionNoticePlugin extends BasePlugin{
    /**
     * 将错误信息交给ExceptionWorkerService处理
     * @param $event
     * @param $data
     */
    public function errorHappen($event,$data){
        try{
            $ews = new ExceptionWorkerService($this->_di);
            if($data instanceof ErrorObject){
                $ews->push($data);
            }elseif($data instanceof \Exception){
                $err = new ErrorObject();
                $err->line = $data->getLine();
                $err->class= $data->getFile();
                $err->method='';
                $err->msg = $data->getMessage();
                $err->time = time();
                $ews->push($err);
            }
        }catch (\Exception $e){
            //...
            //无法记录错误信息时，要再以其他方式通知管理人员
            $this->haltHappen($event,$data);
        }
    }

    /**
     * 很严重问题发生时，连缓存数据库都无法记录时
     * @param $event
     * @param $data
     */
    public function haltHappen($event,$data){
        try{

        }catch(\Exception $e){

        }
    }
}