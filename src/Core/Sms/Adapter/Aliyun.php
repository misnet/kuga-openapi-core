<?php

namespace Kuga\Core\Sms\Adapter;

use Kuga\Core\Base\ErrorObject;

use Kuga\Core\Sms\SendmsgLogsModel;
use Kuga\Core\Sms\SmsInterface;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
/**
 * 阿里云手机短信发送
 *
 * @author dony
 *
 */
class Aliyun implements SmsInterface
{

    protected static $config;

    protected static $di;

    public function __construct($configFile, $di = null)
    {
        self::$di = $di ? $di : new \Phalcon\DI\FactoryDefault();;
        $translator = self::$di->getShared('translator');
        if ( ! file_exists($configFile)) {
            $errObj         = new ErrorObject();
            $errObj->line   = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '阿里云的短信配置文件没配置';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen', $errObj);

            throw new \Exception($translator->_('阿里云的短信配置文件不存在'));
        }
        $content                     = file_get_contents($configFile);
        $option                      = json_decode($content, true);
        self::$config['regionId']    = '';
        self::$config['appKey']      = '';
        self::$config['appSecret']   = '';
        self::$config['uid']         = '';
        self::$config['template']    = [];
        self::$config['productName'] = '';
        self::$config['signName']    = '';
        self::$config                = \Qing\Lib\Utils::arrayExtend(self::$config, $option);
    }

    public function getConfig()
    {
        return self::$config;
    }

    /**
     * 发送短信
     *
     * @param unknown $to     接收手机号
     * @param unknown $tplId  模板ID
     * @param string  $params 参数，示例： {"code":"9087"}
     * @param array   $extendInfo
     * @param string  $logMsg 日志
     *
     * @return boolean
     */
    public function send($to, $tplId, $params = '', $extendInfo = [], $logMsg = '')
    {

        if (is_array($to)) {
            $to = join(',', $to);
        }

        AlibabaCloud::accessKeyClient(self::$config['appKey'], self::$config['appSecret'])->regionId(self::$config['regionId'])->asGlobalClient();
        $queryParams = [
            'PhoneNumbers'=>strval($to),
            'SignName'=>self::$config['signName'],
            'TemplateCode'=>$tplId
        ];

        $logModel = new SendmsgLogsModel();
        if ($logMsg == '') {
            $logMsg = '短信模板：'.$tplId;
        }
        $logModel->msgBody   = $logMsg;
        $logModel->msgSender = get_called_class();
        $logModel->msgTo     = $to;
        $logModel->sendTime  = time();

        try {
            if ($params) {
                if (is_array($params)) {
                    $params = json_encode($params);
                }
                $queryParams['TemplateParam'] = $params;
            }
            $result = AlibabaCloud::rpcRequest()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => $queryParams
                ])
                ->request();
            $response = $result->toArray();
            $logModel->msgId = $response['RequestId'];
            $logModel->create();
            return true;
        } catch (ClientException $e) {
            $errObj         = new ErrorObject();
            $errObj->line   = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '短信发送异常('.$e->getErrorMessage().')';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen', $errObj);
            return false;
        } catch (ServerException $e) {
            $errObj         = new ErrorObject();
            $errObj->line   = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '短信发送异常('.$e->getErrorMessage().')';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen', $errObj);
            return false;
        }

    }

    /**
     * 验证码
     *
     * @param string $to   手机号
     * @param string $code 验证码
     *
     * @return boolean
     */
    public function verifyCode($to, $code)
    {
        $params = '{"code":"'.$code.'","product":"'.self::$config['productName'].'"}';
        $logMsg = '验证码:'.$code;
        if (isset(self::$config['template']['verify']) && self::$config['template']['verify']) {
            return $this->send($to, self::$config['template']['verify'], $params, $logMsg);
        } else {
            //ERR:配置信息
            $errObj         = new ErrorObject();
            $errObj->line   = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '阿里云的验证码模板没配置';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen', $errObj);
        }
    }

    /**
     * 发注册用验证码
     *
     * @param unknown $to
     * @param unknown $code
     * @param array   $extendInfo
     *
     * @return boolean
     */
    public function registerCode($to, $code, $extendInfo = [])
    {
        $params = '{"code":"'.$code.'","product":"'.self::$config['productName'].'"}';
        $logMsg = '注册验证码:'.$code;
        if (self::$config['template']['register'] && self::$config['template']['register']) {
            return $this->send($to, self::$config['template']['register'], $params, $logMsg);
        } else {
            //ERR:配置信息
            $errObj         = new ErrorObject();
            $errObj->line   = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '阿里云短信的注册验证码模板没配置';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen', $errObj);
        }
    }


}