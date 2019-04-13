<?php

namespace Kuga\Core\Model;

use Kuga\Core\Base\AbstractModel;

class ApiLogModel extends AbstractModel
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $method;

    /**
     *
     * @var string
     */
    public $mid;

    /**
     *
     * @var integer
     */
    public $duration;

    public $requestTime;

    public $responseTime;

    public $userIp;

    public $params;

    public $result;

    public $redisId;

    public function getSource()
    {
        return 't_api_logs';
    }

    public function initialize()
    {
        parent::initialize();
        $config = $this->getDI()->getShared('config');
        if ($config->dbRead->statsDbname) {
            $this->setSchema($config->dbRead->statsDbname);
        }
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return ['id'            => 'id', 'method' => 'method',
                'request_time'  => 'requestTime',
                'response_time' => 'responseTime', 'mid' => 'mid',
                'user_ip'       => 'userIp', 'params' => 'params',
                'result'        => 'result', 'redis_id' => 'redisId',
                'duration'      => 'duration'];
    }
}
