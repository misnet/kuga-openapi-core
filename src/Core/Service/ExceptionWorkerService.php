<?php

namespace Kuga\Core\Service;

use Kuga\Core\Base\AbstractService;
use Kuga\Core\Base\ErrorObject;
use Kuga\Core\SysParams\SysParamsModel;

/**
 * 异常记录处理类，将一些异常记下来，通知管理人员
 *
 * @author Donny
 *
 */
class ExceptionWorkerService extends AbstractService
{

    const  LOG_ID_NAME = 'LOG_ID';

    const  LOG_LIST = 'LOG_LIST';

    const  ERR_NOTICE_LIST = 'ERR_NOTICE';

    const  PREFIX = 'ERR';

    const  LOG_KEY = 'ERRLOG';

    /**
     * 发短信不成功时，最多可重试的次数
     */
    const  MAX_RETRYTIME = 10;

    /**
     * 发短信时，错误内容描述最多字数
     */
    const  SMS_MAX_LEN = 40;

    /**
     * redis 库名
     *
     * @var integer
     */
    //private $dbIndex = 1;
    /**
     *
     * @var \Qing\Lib\SimpleStorage
     */
    private $storage;

    private $_cacheData;

    public function __construct($di = null)
    {
        parent::__construct($di);
        $this->storage    = $this->_di->get('simpleStorage');
        $this->_cacheData = [];
    }

    /**
     * 记录异常情况
     *
     * @param \Kuga\DTO\ErrorObject
     *
     * @return boolean|\Qing\Lib\NULL|string|number
     */
    public function push(ErrorObject $err)
    {
        $now = $err->time ? $err->time : time();
        $id  = $this->storage->incrementBy(self::PREFIX.':'.self::LOG_ID_NAME);
        $this->storage->addToSortedSet(self::PREFIX.':'.self::LOG_LIST, $id, $now);
        $this->storage->setToHash(
            self::PREFIX.':'.self::LOG_KEY.':'.$id,
            ['class' => $err->class, 'method' => $err->method, 'createTime' => $now, 'msg' => $err->msg,
             'ip'    => \Qing\Lib\Utils::getClientIp(), 'line' => $err->line, 'noticeTryTime' => 0, 'noticedTime' => 0]
        );
        //记录通知队列
        $this->storage->prependToList(self::PREFIX.':'.self::ERR_NOTICE_LIST, $id);

        return $id;
    }

    /**
     * 检测待通知列表，通知运维人员
     *
     * @param int $limit
     */
    public function noticeDevops($limit = 10)
    {
        $list = $this->storage->getList(self::PREFIX.':'.self::ERR_NOTICE_LIST, 0, $limit);
        if ($list) {
            foreach ($list as $id) {
                $errorInfo       = $this->storage->getFromHash(self::PREFIX.':'.self::LOG_KEY.':'.$id);
                $errorInfo['id'] = $id;
                $this->errorNotice($errorInfo);
            }
        }
    }

    /**
     * 错误发生时通知运维人员
     *
     * @param array $errorInfo
     */
    private function errorNotice($errorInfo)
    {

        //通知过了也不要再通知
        if (isset($errorInfo['noticedTime']) && $errorInfo['noticedTime'] > 0) {
            return;
        }

        //通知超过限定次数时不能可再通知
        if ( ! isset($errorInfo['noticeTryTime']) || intval($errorInfo['noticeTryTime']) >= self::MAX_RETRYTIME) {
            return;
        }


        $config = $this->_di->get('sms')->getConfig();
        $tmpId  = $config['template']['devops'];
        $to     = SysParamsModel::getInstance()->get('sms.devops_mobiles');
        if ($to && $tmpId && $errorInfo && isset($errorInfo['id'])) {
            $params['errorId'] = $errorInfo['id'];
            $params['msg']     = $errorInfo['msg'];
            $params['summary'] = \Qing\Lib\Utils::shortWrite($params['msg'], self::SMS_MAX_LEN);
            $result            = $this->_di->get('sms')->send($to, $tmpId, $params);
            if ($result) {
                $this->storage->setToHash(
                    self::PREFIX.':'.self::LOG_KEY.':'.$errorInfo['id'],
                    ['noticeTryTime' => $errorInfo['noticeTryTime'] + 1, 'noticedTime' => time()]
                );
                //成功通知就可以从队列中去掉
                $this->storage->deleteFromList(self::PREFIX.':'.self::ERR_NOTICE_LIST, $errorInfo['id']);
            } else {
                $this->storage->setToHash(
                    self::PREFIX.':'.self::LOG_KEY.':'.$errorInfo['id'],
                    ['noticeTryTime' => $errorInfo['noticeTryTime'] + 1]
                );
            }
        }
    }

    /**
     * 删除指定的记录
     *
     * @param $id
     */
    public function removeById($id)
    {
        if (is_int($id)) {
            $this->storage->delete(self::PREFIX.':'.self::LOG_KEY.':'.$id);
            $this->storage->deleteFromSortedSet(self::PREFIX.':'.self::LOG_LIST, $id);
            $this->storage->deleteFromList(self::PREFIX.':'.self::ERR_NOTICE_LIST, $id);
        }
    }

    /**
     * 删除指定时间点之前的所有记录
     *
     * @param $time
     */
    public function removeByMaxTime($time)
    {
        $fromTime = 0;
        $endTime  = $time;
        $total    = $this->count($fromTime, $endTime);
        if ($total) {
            $list = $this->getList(1, $total, $fromTime, $endTime);
            if ( ! empty($list)) {
                $ids = [];
                foreach ($list as $item) {
                    $ids[] = $item['id'];
                    $this->storage->deleteFromSortedSet(self::PREFIX.':'.self::LOG_LIST, $item['id']);
                }
                $this->storage->delete($ids);
            }
        }
    }

    public function flush()
    {
        $this->storage->delete(self::PREFIX.':'.self::LOG_LIST);
        $this->storage->set(self::PREFIX.':'.self::LOG_ID_NAME, 1);
        $this->storage->deleteKeys(self::PREFIX.':'.self::LOG_KEY.'*');
        $this->storage->delete(self::PREFIX.':'.self::ERR_NOTICE_LIST);
    }

    /**
     * 取得记录总数
     *
     * @param integer $fromTime
     * @param integer $endTime
     *
     * @return number
     */
    public function count($fromTime = 0, $endTime = 0)
    {
        $key = md5(__METHOD__.':'.serialize(func_get_args()));
        if ($endTime === 0) {
            $endTime = time() + 1000;
        }
        if (isset($this->_cacheData[$key])) {
            return $this->_cacheData[$key];
        } else {
            $this->_cacheData[$key] = $this->storage->getSortedSetLengthByScore(
                self::PREFIX.':'.self::LOG_LIST, $fromTime, $endTime
            );

            return $this->_cacheData[$key];
        }
    }

    /**
     * 取得访问列表
     *
     * @param number $page
     * @param number $limit
     * @param number $fromTime
     * @param number $endTime
     *
     * @return array
     */
    public function getList($page = 1, $limit = 10, $fromTime = 0, $endTime = 0)
    {
        $start = ($page - 1) * $limit;
        if ($endTime === 0) {
            $endTime = time() + 1000;
        }
        $list = $this->storage->getFromSortedSetByScore(
            self::PREFIX.':'.self::LOG_LIST, $fromTime, $endTime, false, $limit, $start, true
        );
        if ($list) {
            $array = [];
            foreach ($list as $invokeId) {
                $tmp       = $this->storage->getFromHash(self::PREFIX.':'.self::LOG_KEY.':'.$invokeId);
                $tmp['id'] = $invokeId;
                $array[]   = $tmp;
            }

            return $array;
        } else {
            return [];
        }
    }
}