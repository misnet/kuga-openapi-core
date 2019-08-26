<?php
namespace Kuga\Core\Model;
use Kuga\Core\Base\AbstractModel;

/**
 * 1.前端创建任务对象，指定相关参数
 * 2.后端定时执行任务
 * 3.任务执行完后，提供回馈机制
 *
create table t_tasks(
id varchar(32) not null,
submit_time int not null comment '提交时间',
start_time int not null comment '开始时间',
end_time int not null comment '结束时间',
uid int not null comment '执行人',
task_type varchar(30) not null default '' comment '任务类型',
request_json text not null comment '请求参数',
response_json text not null comment '响应参数',
primary key(id),
index(task_type),
index(submit_time)
)comment='任务表'
 * Class TaskModel
 * @package Kug\Core\Model
*/
class TaskModel extends  AbstractModel{
    /**
     * GUID
     * @var string
     */
    public $id;
    /**
     * 任务提交时间
     * @var  int
     */
    public $submitTime;
    /**
     * 任务开始执行时间
     * @var int
     */
    public $startTime;
    /**
     * 任务执行结束时间
     * @var int
     */

    public $endTime;
    /**
     * 查询结果的KEY值
     * @var String
     */
    public $queryKey;
    /**
     * 任务类型
     * @var
     */
    public $taskType;
    /**
     * 任务提出人ID
     * @var
     */
    public $uid;
    /**
     * 响应结果
     * @var string
     */
    public $responseJson;
    /**
     * 任务请求相关参数JSON
     * @var string
     */
    public $requestJson;

    public function getSource() {
        return 't_tasks';
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {

        return  array (
            'id' => 'id',
            'submit_time' => 'submitTime',
            'start_time' => 'startTime',
            'end_time' => 'endTime',
            'response_json'=>'responseJson',
            'request_json'=>'requestJson',
            'uid'=>'uid',
            'task_type'=>'taskType',
            'query_key'=>'queryKey'
        );

    }
}