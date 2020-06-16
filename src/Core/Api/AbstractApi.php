<?php
/**
 * API抽象类, 所有的API类要继承本方法
 *
 * @author    Donny
 * @copyright 2017
 */

namespace Kuga\Core\Api;

use Kuga\Core\Base\AbstractService;
use Kuga\Core\GlobalVar;
//use Kuga\Core\SysParams\SysParamsModel;
use Kuga\Core\Api\Exception as ApiException;

use Kuga\Core\Service\JWTService;
use Phalcon\Http\Client\Request as HttpClientRequest;

abstract class AbstractApi extends AbstractService
{

    /**
     * API参数
     *
     * @var array
     */
    protected $_params;

    /**
     * API方法
     *
     * @var
     */
    protected $_method;

    /**
     * 当前用户ID，可能是前台，也可能是后台
     *
     * @var
     */
    protected $_userMemberId;

    protected $_testModel;

    /**
     * APP KEY，可用于处理不同调用通道
     * IOS APP, Android APP, Mobile Web, PC Web等要有不同的KEY
     *
     * @var string
     */
    protected $_appKey;

    protected $_appSecret;

    /**
     * 参数白名单
     *
     * @var array
     */
    protected $_whiteProps = [];

    protected $_blackProps = [];

    protected $_accessToken;
    protected $_version;
    protected $_locale;

    /**
     * 对accessToken的等级要求
     * 0 不需要，有传也会被过滤掉
     * 1 强制需要，必须有
     * 2 可要，可不要，有传就验证，不传就当没有
     *
     * @var int
     */
    protected $_accessTokenRequiredLevel = 0;
    protected $_accessTokenType = 'KUGA'; //值有KUGA，JWT两种

    private $_logger;

    protected $_accessTokenUserIdKey = 'uid';

    public function setAccessTokenUserIdKey($k){
        $this->_accessTokenUserIdKey = $k;
    }
    public function validateAppKey()
    {
        $cacheId = GlobalVar::APPLIST_CACHE_ID;
        $cache = self::$di->getShared('cache');
        $data = $cache->get($cacheId);
        if ($data) {
            $apiKeys = $data;
        } else {
            $apiKeys = [];
        }
        if (!array_key_exists($this->_appKey, $apiKeys)) {
            throw new ApiException(ApiException::$EXCODE_INVALID_CLIENT);
        }
        if ($this->_appSecret != $apiKeys[$this->_appKey]['secret']) {
            throw new ApiException(ApiException::$EXCODE_INVALID_CLIENT);
        }
    }

    /**
     * 设置accessToken的需求等级
     *
     * @param $level
     *
     * @return mixed
     */
    public function setAccessTokenRequiredLevel($level)
    {
        return $this->_accessTokenRequiredLevel = $level;
    }

    public function setAppKey($s)
    {
        $this->_appKey = $s;
    }

    public function getAppKey()
    {
        return $this->_appKey;
    }

    public function getAppSecret()
    {
        return $this->_appSecret;
    }

    public function setAppSecret($s)
    {
        $this->_appSecret = $s;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function setVersion($v)
    {
        $this->_version = $v;
    }

    public function getLocale()
    {
        return $this->_locale;
    }

    public function setLocale($v)
    {
        $this->_locale = $v;
    }

    /**
     * 设置AccessToken
     *
     * @param $token
     */
    public function setAccessToken($token)
    {
        $this->_accessToken = $token;
    }


    /**
     * 是否是IOS
     *
     * @return bool
     */
    public function isIOS()
    {
        return $this->getAppKey() == '1001';
    }

    /**
     * 初始化API传参
     *
     * @param Array $params
     * @param null $di
     * @param null $method
     */
    public function initParams($params, $method = null)
    {
        //$this->_params = new Parameter($params);
        $this->_params = $params;
        $this->_method = $method;
        if ($this->_accessToken && $this->_accessTokenRequiredLevel > 0) {
            $this->_userMemberId = $this->_getInfoFromAccessToken($this->_accessToken, $this->_accessTokenUserIdKey);
        }
        $this->_testModel = $this->_di->get('config')->get('testmodel');
    }

    /**
     * 调用API响应方法之前要处理的内容
     * 增加此空方法，方便子对类根据需要进行响应之前的预处理
     */
    public function beforeInvoke()
    {

    }

    /**
     * 设置AccessToken的类型
     * @param $t 类型
     */
    public function setAccessTokenType($t){
        $this->_accessTokenType = $t;
    }
    /**
     * 取得当前用户的ID
     *
     * @return Integer
     */
    public function getUserMemberId()
    {
        return $this->_userMemberId;
    }

    public function setUserMemberId($uid)
    {
        $this->_userMemberId = $uid;
    }

    /**
     * 取得传进来的参数数组
     *
     * @return array
     */
    protected function getParams()
    {
        return $this->_params;
    }

    /**
     * 将API数组参数转为Parameter对象
     *
     * @param Array $data
     * @param unknown $whiteProps
     * @param string $restrict
     *
     * @return \Kuga\Core\Api\Parameter
     * @throws Exception
     */
    protected function _toParamObject($data, $whiteProps = [], $restrict = false)
    {
        $returnData = [];
        if (empty($whiteProps)) {
            $whiteProps = $this->_whiteProps;
        }
        if (!empty($whiteProps) && $data) {
            foreach ($data as $key => $value) {
                //根据值来判断
                if (in_array($key, $whiteProps) && !in_array($key, $this->_blackProps)) {
                    $returnData[$key] = $value;
                }
            }
        } else {
            $returnData = $data;
        }
        if ($restrict && sizeof($returnData) != sizeof($whiteProps) && sizeof($whiteProps) > 0) {
            throw new ApiException(ApiException::$EXCODE_PARAMMISS);
        }

        return new Parameter($returnData);
    }

    /**
     * 从加密的accessToken解出想要的信息
     *
     * @param        $accessToken
     * @param string $key
     *
     * @return \Kuga\Service\unknown|NULL
     * @throws Exception
     */
    protected function _getInfoFromAccessToken($accessToken, $key = '')
    {

        if($this->_accessTokenType === GlobalVar::TOKEN_TYPE_JWT){
            return $this->_getInfoFromJsonWebToken($accessToken,$key);
        }else{
            $at = ApiService::decryptData($accessToken);
            if (!$at) {
                throw new ApiException(ApiException::$EXCODE_INVALID_ACCESSTOKEN);
            } else {
                if ($key) {
                    return !isset($at[$key]) ? null : $at[$key];
                } else {
                    return $at;
                }
            }
        }
    }

    /**
     * 生成Token
     *
     * @param mixed $data 用户ID
     * @param integer $lifetime Token的有效时间
     *
     * @return \Phalcon\string
     */
    protected function _createAccessToken($data, $lifetime = 0)
    {
        if($this->_accessTokenType === GlobalVar::TOKEN_TYPE_JWT){
            return $this->_createJsonWebToken($data,$lifetime);
        }else{
            $lifetime = intval($lifetime);
            $lifetime || $lifetime = 864000;
            ApiService::setLifetime($lifetime);
            return ApiService::cryptData($data);
        }

    }

    /**
     * 创建JWT
     * @param $data
     * @param int $lifetime
     * @return string
     */
    protected function _createJsonWebToken($data, $lifetime = 0)
    {
        $secret = $this->_di->get('config')->get('jwtTokenSecret');
        $jwtService = new JWTService();
        $jwtService->setSecret($secret);
        return $jwtService->createToken($data,$lifetime);
    }
    protected function _getInfoFromJsonWebToken($accessToken, $key = '')
    {
        $secret = $this->_di->get('config')->get('jwtTokenSecret');
        $jwtService = new JWTService();
        $jwtService->setSecret($secret);
        $at =  $jwtService->validate($accessToken);
        if(!$at){
            throw new ApiException(ApiException::$EXCODE_INVALID_ACCESSTOKEN);
        }else{
            if ($key) {
                return !isset($at[$key]) ? null : $at[$key];
            } else {
                return $at;
            }
        }
    }
    /**
     * HMACSHA256签名   https://jwt.io/  中HMACSHA256签名实现
     * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
     * @param string $key
     * @param string $alg   算法方式
     * @return mixed
     */
    private static function signature(string $input, string $key, string $alg = 'HS256')
    {
        $alg_config=array(
            'HS256'=>'sha256'
        );
        return self::base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key,true));
    }
    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    private static function base64UrlDecode(string $input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
    /**
     * API日志
     *
     * @param $message
     */
    protected function _log($message)
    {
        if (!$this->_logger) {
            $this->_logger = $this->_di->getShared('logger');
        }
        if (is_object($message) || is_array($message)) {
            $message = print_r($message, true);
        }
        $this->_logger->log($message);
    }

    /**
     * 只返回白名单中的key
     *
     * @param array $data
     * @param array $whiteProps 白名单key
     *
     * @return array
     */
    protected function _filter($data, $whiteProps = [])
    {
        $returnData = [];
        if (!empty($whiteProps) && $data) {
            foreach ($data as $key => $value) {
                //根据值来判断
                if (in_array($key, $whiteProps)) {
                    $returnData[$key] = $value;
                }
            }
        } else {
            $returnData = $data;
        }

        return $returnData;
    }

    /**
     * @return \Phalcon\Events\ManagerInterface
     */
    protected function getEventsManager()
    {
        return $this->_di->getShared('eventsManager');
    }

    /**
     * 验证手机有效性
     *
     * @param $countryCode
     * @param $mobile
     */
    protected function validMobile($countryCode, $mobile)
    {
        $t = $this->translator;
        if (!preg_match('/^(\d+)$/i', $countryCode)) {
            throw new ApiException($t->_('国家区号不正确'));
        }
        if (!preg_match('/^(\d+)$/i', $mobile)) {
            throw new ApiException($t->_('手机号不正确'));
        }
    }

    /**
     * 当前账号是否是开发人员账号，用于特权处理
     *
     * @return bool
     */
//    protected function isDevMember()
//    {
//        $devMembers  = SysParamsModel::getInstance()->get('app.devMembers');
//        $devMidArray = explode(',', $devMembers);
//        if ($devMembers && sizeof($devMidArray) > 0) {
//            if ($this->_userMemberId && in_array($this->_userMemberId, $devMidArray)) {
//                return true;
//            }
//        }
//
//        return false;
//    }

    protected function apiRequest($method, $params, $hostUrl, $path)
    {

        $params['appkey'] = $this->getAppKey();
        $secret = $this->getAppSecret();
        $params['access_token'] = $this->_accessToken;
        $params['method'] = $method;
        $params['sign'] = Request::createSign($secret, $params);
        $provider = HttpClientRequest::getProvider();
        $provider->setBaseUri($hostUrl);
        $provider->header->set('Accept', 'application/json');
        $response = $provider->post($path, $params);
        return $response->body;
    }
}
