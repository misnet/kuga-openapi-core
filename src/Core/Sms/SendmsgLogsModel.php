<?php

namespace Kuga\Core\Sms;

use Kuga\Core\Base\AbstractModel;

class SendmsgLogsModel extends AbstractModel
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
    public $msgTo;

    /**
     *
     * @var string
     */
    public $msgBody;

    /**
     *
     * @var string
     */
    public $msgId;

    /**
     *
     * @var string
     */
    public $msgSender;

    /**
     *
     * @var string
     */
    public $errorInfo;

    /**
     *
     * @var integer
     */
    public $sendState;

    /**
     *
     * @var integer
     */
    public $sendTime;

    public function getSource()
    {
        return 't_sendmsg_logs';
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return ['id'         => 'id', 'msg_to' => 'msgTo', 'msg_body' => 'msgBody', 'msg_id' => 'msgId', 'msg_sender' => 'msgSender',
                'error_info' => 'errorInfo', 'send_state' => 'sendState', 'send_time' => 'sendTime'];
    }
}
