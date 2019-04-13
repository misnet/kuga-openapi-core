<?php
namespace Kuga\Core\Sms\Adapter;
use Kuga\Core\Base\ErrorObject;
use Kuga\Core\Base\ServiceException;
use Kuga\Core\GlobalVar;
use Kuga\Core\Sms\SendmsgLogsModel;
use Kuga\Core\Sms\SmsInterface;
use Qcloud\Sms\SmsMultiSender as MultiSender;
use Qcloud\Sms\SmsSingleSender as SingleSender;

/**
 * 腾讯云手机短信发送
 * @author dony
 *
 */
class Tencent implements SmsInterface {
    protected static $config;
    protected static $di;
    /**
     * 1为营销，0为普通
     * @var int
     */
    private $smsType = 0;
    public  function __construct($configFile,$di=null){
        self::$di   = $di?$di:new \Phalcon\DI\FactoryDefault();;
        $translator = self::$di->getShared('translator');
        if(!file_exists($configFile)){
            $errObj = new ErrorObject();
            $errObj->line = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '腾讯云的短信配置文件没配置';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen',$errObj);

            throw new \Exception($translator->_('腾讯云的短信配置文件不存在'));
        }
        $content = file_get_contents($configFile);
        $option = json_decode($content, true);

        self::$config['appId'] = '';
        self::$config['appSecret'] = '';
        self::$config['template'] = [];
        self::$config['productName'] = '';
        self::$config['signName'] = '';
        self::$config = \Qing\Lib\Utils::arrayExtend ( self::$config, $option );
    }
    public  function getConfig(){
        return self::$config;
    }
    public function setType($t){
        $this->smsType = $t;
    }
    /**
     * 发送短信
     * @param unknown $to 接收手机号
     * @param unknown $tplId 模板ID
     * @param string $params 参数，示例： {"code":"9087"}
     * @param array $extendInfo
     * @param string $logMsg 日志
     * @return boolean
     */
    public  function send($to,$tplId,$params='',$extendInfo=[],$logMsg=''){
        $sendParams=[];
        if(is_array($params)) {
            foreach ($params as $p) {
                $sendParams[] = $p;
            }
        }elseif(is_string($params) && stripos($params,'{')===0){
            $paramsArray = json_decode($params,true);
            foreach ($paramsArray as $p) {
                $sendParams[] = $p;
            }
        }else{
            $sendParams[] = $params;
        }
        $logModel = new SendmsgLogsModel();
        if($logMsg==''){
            $logMsg = '短信模板：'.$tplId;
        }
        $logModel->msgBody = $logMsg;
        $logModel->msgSender = get_called_class();
        $logModel->msgTo     = $to;
        $logModel->sendTime  = time();
        try {
            if(!is_array($to)){
                $sender = new SingleSender(self::$config['appId'],self::$config['appSecret']);
                $result = $sender->sendWithParam(GlobalVar::COUNTRY_CODE_CHINA,$to,$tplId,$sendParams,self::$config['signName']);
            }else{
                $sender = new MultiSender(self::$config['appId'],self::$config['appSecret']);
                $result = $sender->sendWithParam(GlobalVar::COUNTRY_CODE_CHINA,$to,$tplId,$sendParams,self::$config['signName']);
            }
            $response = json_decode($result,true);
            if(isset($response['ErrorCode'])){
                return false;
            }else{

                $logModel->sendState = $response['result'];
                if(intval($response['result'])!==0){
                    $logModel->errorInfo = isset($response['errmsg'])?$response['errmsg']:'未知错误';
                }
                $logModel->msgId = isset($response['sid'])?$response['sid']:'';
                $logModel->create();
                return $response['result']==0;
            }
        }
        catch (\Exception  $e) {
            $errObj = new ErrorObject();
            $errObj->line = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '短信发送异常('.$e->getMessage().')';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen',$errObj);
            return false;
        }
    }
    /**
     * 验证码
     * @param string $to 手机号
     * @param string $code 验证码
     * @return boolean
     */
    public  function verifyCode($to,$code){
        $params = '{"code":"'.$code.'","product":"'.self::$config['productName'].'"}';
        $logMsg = '验证码:'.$code;
        if(isset(self::$config['template']['verify']) && self::$config['template']['verify']){
            return $this->send($to, self::$config['template']['verify'], $params, $logMsg);
        }else{
            //ERR:配置信息
            $errObj = new ErrorObject();
            $errObj->line = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '腾讯云的验证码模板没配置';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen',$errObj);
            throw new ServiceException('Sms template id empty');
        }
    }
    /**
     * 发注册用验证码
     * @param unknown $to
     * @param unknown $code
     * @param array $extendInfo
     * @return boolean
     */
    public  function registerCode($to,$code,$extendInfo=array()){
        $params = '{"code":"'.$code.'","product":"'.self::$config['productName'].'"}';
        $logMsg = '注册验证码:'.$code;
        if(self::$config['template']['register'] && self::$config['template']['register']){
            return $this->send($to, self::$config['template']['register'], $params, $logMsg);
        }else{
            //ERR:配置信息
            $errObj = new ErrorObject();
            $errObj->line = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '腾讯云短信的注册验证码模板没配置';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen',$errObj);
            throw new ServiceException('Sms template id empty');
        }
    }


}