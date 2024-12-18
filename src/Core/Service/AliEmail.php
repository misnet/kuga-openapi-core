<?php
namespace Kuga\Core\Service;
use Kuga\Core\Base\ErrorObject;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
class AliEmail{
    protected static $config;
    protected static $di;
    public  function __construct($option,$di=null){
        self::$di   = $di?$di:new \Phalcon\DI\FactoryDefault();;
        $translator = self::$di->getShared('translator');
        if(empty($option)){
            $errObj = new ErrorObject();
            $errObj->line = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '阿里云的邮件配置没配置';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen',$errObj);
            throw new \Exception($translator->_('阿里云的邮件配置不存在'));
        }
        self::$config['regionId'] = '';
        self::$config['appKey'] = '';
        self::$config['appSecret'] = '';
        self::$config['triggerEmail'] = '';
        self::$config['templateDir'] = '/tmp';
        self::$config = \Qing\Lib\Utils::arrayExtend ( self::$config, $option );
    }

    /**
     * 发送邮件验证码
     * @param $toEmail 接收邮箱
     * @param $code 验证码
     * @return bool
     */
    public function verifyCode($toEmail,$code){
        $locale = self::$di->getShared('translator')->getLocale();
        $lang = strstr($locale,'.'.self::$di->getShared('config')->path('app.charset'),true);

        $lang = $lang?$lang:'zh_CN';
        $emailFile = dirname(self::$config['templateDir']).DS.'verify.'.$lang.'.html';
        if(file_exists($emailFile)){
            $content = file_get_contents($emailFile);
            $content = str_replace('{verifyCode}',$code,$content);
        }else{
            $content = self::$di->getShared('translator')->_('您的验证码是%s%',['s'=>$code]);
        }
        $subject = self::$di->getShared('translator')->_('验证码');
        return $this->send($toEmail,$subject,$content);
    }
    /**
     * 发送单封邮件
     * @param $toEmail 收件地址
     * @param $subject 主题
     * @param $content 内容
     * @param string $senderAlias 发送人别我
     */
    public function send($toEmail,$subject,$content,$senderAlias=''){

        AlibabaCloud::accessKeyClient(self::$config['appKey'], self::$config['appSecret'])
            ->regionId(self::$config['regionId']) // replace regionId as you need
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dm')
                // ->scheme('https') // https | http
                ->version('2015-11-23')
                ->action('SingleSendMail')
                ->method('POST')
                ->options([
                    'query' => [
                        'AccountName' => self::$config['triggerEmail'],
                        'ToAddress' => $toEmail,
                        'AddressType'=>'1',
                        'ReplyToAddress'=>'false',
                        'Subject' => $subject,
                        'HtmlBody' => $content,
                    ],
                ])
                ->request();
            return true;
        } catch (ClientException $e) {
            $errObj         = new ErrorObject();
            $errObj->line   = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '邮件发送异常('.$e->getErrorMessage().')';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen', $errObj);
            return false;
        } catch (ServerException $e) {
            $errObj         = new ErrorObject();
            $errObj->line   = __LINE__;
            $errObj->method = __METHOD__;
            $errObj->class  = __CLASS__;
            $errObj->msg    = '邮件发送异常('.$e->getErrorMessage().')';
            self::$di->getShared('eventsManager')->fire('qing:errorHappen', $errObj);
            return false;
        }
    }
}
