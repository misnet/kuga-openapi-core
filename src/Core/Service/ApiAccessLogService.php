<?php

namespace Kuga\Core\Service;

use Phalcon\Di\FactoryDefault;

/**
 * API记录访问日志
 *
 * @author Donny
 *
 */
class ApiAccessLogService
{

    const  LOG_ID_NAME = 'LOG_ID';

    const  LOG_LIST = 'LOG_LIST';

    const  PREFIX = 'API';

    /**
     * redis 库名
     *
     * @var integer
     */
    private $dbIndex = 1;

    /**
     *
     * @var \Qing\Lib\SimpleStorage
     */
    private $storage;

    /**
     * @var \Phalcon\Di\DiInterface
     */
    protected $_di;

    public function __construct($di = null)
    {
        if (is_null($di)) {
            $this->_di = new FactoryDefault();
        } else {
            $this->_di = $di;
        }
        $this->storage = $this->_di->get('simpleStorage');
    }

    /**
     * 记录访问情况
     *
     * @param unknown $method
     * @param unknown $params
     *
     * @return boolean|\Qing\Lib\NULL|string|number
     */
    public function init($method, $params)
    {
        $id = $this->storage->incrementBy(self::PREFIX.':'.self::LOG_ID_NAME);
        //$this->storage->prependToList(self::PREFIX.':'.self::LOG_LIST, $id);
        $this->storage->setToHash(
            self::PREFIX.':LOG:'.$id, ['method'     => $method,
                'params'     => $params,
                'createTime' => microtime(true),
                'ip'         => \Qing\Lib\Utils::getClientIp(
                )]
        );
        $this->storage->addToSortedSet(
            self::PREFIX.':'.self::LOG_LIST, $id, time()
        );

        return $id;
    }

    public function setResult($id, $result)
    {
        $this->storage->setToHash(
            self::PREFIX.':LOG:'.$id,
            ['result' => $result, 'responseTime' => microtime(true)]
        );
    }

    public function setAccessMemberId($id, $memberId)
    {
        $this->storage->setToHash(
            self::PREFIX.':LOG:'.$id, ['memberId' => $memberId]
        );
    }

    /**
     * 清空数据
     */
    public function flush()
    {
        $this->storage->deleteKeys(self::PREFIX.':*');
    }

    /**
     * 指定时间之前的记录删除
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
                    $ids[] = self::PREFIX.':LOG:'.$item['id'];
                    $this->storage->deleteFromSortedSet(
                        self::PREFIX.':'.self::LOG_LIST, $item['id']
                    );
                }
                $this->storage->delete($ids);
            }
        }
    }

    public function removeByIds($ids)
    {
        $this->storage->begin();
        if ($ids) {
            $removeIds = [];
            foreach ($ids as $id) {
                $removeIds[] = self::PREFIX.':LOG:'.$id;
                $this->storage->deleteFromSortedSet(
                    self::PREFIX.':'.self::LOG_LIST, $id
                );
            }
            $this->storage->delete($removeIds);
        }

        $this->storage->commit();
    }

    /**
     * 取得记录总数
     *
     * @return number
     */
    public function count($startTime = '-inf', $endTime = '+inf')
    {
        //return $this->storage->getListLength(self::PREFIX.':'.self::LOG_LIST);
        $startTime = intval($startTime);
        $startTime || $startTime = '-inf';

        $endTime = intval($endTime);
        $endTime || $endTime = '+inf';
        return $this->storage->getSortedSetLengthByScore(
            self::PREFIX.':'.self::LOG_LIST, $startTime, $endTime
        );
    }

    /**
     * 取得访问列表
     *
     * @param number $page
     * @param number $limit
     *
     * @return array
     */
    public function getList($page = 1, $limit = 10, $startTime = '0',
                            $endTime = '0', $revert = true
    ) {
        $start = ($page - 1) * $limit;
        $total = $this->count();
        $end   = $start + $limit - 1;
        $end   = min($end, $total);

        $startTime = intval($startTime);
        $startTime || $startTime = '-inf';

        $endTime = intval($endTime);
        $endTime || $endTime = '+inf';

        //$list  =  $this->storage->getList(self::PREFIX.':'.self::LOG_LIST,$start,$end);
        $list = $this->storage->getFromSortedSetByScore(
            self::PREFIX.':'.self::LOG_LIST, $startTime, $endTime, false,
            $limit, $start, $revert
        );

        if ($list) {
            $array = [];
            foreach ($list as $invokeId) {
                $tmp       = $this->storage->getFromHash(
                    self::PREFIX.':LOG:'.$invokeId
                );
                $tmp['id'] = $invokeId;
                if (isset($tmp['responseTime']) && $tmp['responseTime'] > 0) {
                    $tmp['duration'] = round(
                        $tmp['responseTime'] - $tmp['createTime'], 4
                    );
                } else {
                    $tmp['duration'] = -1;
                }
                $array[] = $tmp;
            }

            return $array;
        } else {
            return [];
        }
    }
}