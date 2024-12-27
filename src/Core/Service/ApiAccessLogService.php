<?php

namespace Kuga\Core\Service;

use Phalcon\Di\FactoryDefault;
use Phalcon\Storage\Adapter\Redis;

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
     * @var \Phalcon\Storage\Adapter\AdapterInterface
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

        $id = $this->storage->increment(self::PREFIX.':'.self::LOG_ID_NAME);
        $adapter = $this->storage->getAdapter();
        if(!$this->storage instanceof Redis){
            throw new \Exception('APIAccessLog Storage must be Redis');
        }
        //$this->storage->prependToList(self::PREFIX.':'.self::LOG_LIST, $id);
        $adapter->hMSet(
            self::PREFIX.':LOG:'.$id, ['method'     => $method,
                'params'     => \json_encode($params,\JSON_UNESCAPED_UNICODE),
                'createTime' => microtime(true),
                'ip'         => \Qing\Lib\Utils::getClientIp(
                )]
        );
        $adapter->zAdd(
            self::PREFIX.':'.self::LOG_LIST, time(), $id
        );

        return $id;
    }

    public function setResult($id, $result)
    {
        $adapter = $this->storage->getAdapter();
        $adapter->hMSet(
            self::PREFIX.':LOG:'.$id,
            ['result' => \json_encode($result,\JSON_UNESCAPED_UNICODE), 'responseTime' => microtime(true)]
        );
    }

    public function setAccessMemberId($id, $memberId)
    {
        $adapter = $this->storage->getAdapter();
        $adapter->hMSet(
            self::PREFIX.':LOG:'.$id, ['memberId' => $memberId]
        );
    }

    /**
     * 清空数据
     */
    public function flush()
    {
        $adapter = $this->storage->getAdapter();
        $keys    = $this->storage->getKeys(self::PREFIX.':');
        if ($keys) {
            $prefix=$this->storage->getPrefix();
            foreach($keys as &$k){
                $k = str_ireplace($prefix,'',$k);
            }
            $adapter->del($keys);
        }
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
                $adapter = $this->storage->getAdapter();
                foreach ($list as $item) {
                    $ids[] = self::PREFIX.':LOG:'.$item['id'];
                    $adapter->zRem(
                        self::PREFIX.':'.self::LOG_LIST, $item['id']
                    );
                }
                $adapter->del($ids);
            }
        }
    }

    public function removeByIds($ids)
    {
        $adapter = $this->storage->getAdapter();
        $adapter->multi();
        if ($ids) {
            $removeIds = [];
            foreach ($ids as $id) {
                $removeIds[] = self::PREFIX.':LOG:'.$id;
                $adapter->zRem(
                    self::PREFIX.':'.self::LOG_LIST, $id
                );
            }
            $adapter->del($removeIds);
        }

        $adapter->exec();
    }

    /**
     * 取得记录总数
     *
     * @return number
     */
    public function count($startTime = '-inf', $endTime = '+inf')
    {
        $adapter = $this->storage->getAdapter();
        $startTime = intval($startTime);
        $startTime || $startTime = '-inf';

        $endTime = intval($endTime);
        $endTime || $endTime = '+inf';
        return $adapter->zCount(
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

        $adapter = $this->storage->getAdapter();

        $options = ['withscores'=>false];
        if($limit!==null && $start!==null){
            $options['limit']= [$start,$limit];
        }
        if($revert){
            $list = $adapter->zRevRangeByScore(self::PREFIX.':'.self::LOG_LIST, $endTime,$start,$options);
        }else{
            $list = $adapter->zRangeByScore(self::PREFIX.':'.self::LOG_LIST, $startTime, $endTime,$options);
        }
//        $list = $adapter->getFromSortedSetByScore(
//            self::PREFIX.':'.self::LOG_LIST, $startTime, $endTime, false,
//            $limit, $start, $revert
//        );

        if ($list) {
            $array = [];
            foreach ($list as $invokeId) {
                $tmp       = $adapter->hGetAll(
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
                $tmp['params'] = json_decode($tmp['params'],true);
                $tmp['result'] = json_decode($tmp['result'],true);
                $array[] = $tmp;
            }

            return $array;
        } else {
            return [];
        }
    }
}