<?php
namespace Kuga\Core\Api\Request;
use Kuga\Core\GlobalVar;
use Kuga\Core\Service\JWTService;

class JwtRequest extends BaseRequest{
    public function getAuthorizationToken(){
        $authorization =  $this->_getHeader('Authorization');
        return str_ireplace('Bearer ','',$authorization);
    }
    private function _getHeader($name){
        return isset($this->_requestHeaders[$name])?$this->_requestHeaders[$name]:null;
    }
    /**
     * 根据Secret验证请求是否有效
     *
     * @param String $secret appSecret
     * @return boolean
     */
    public function validate($secret)
    {
        if($this->getAccessTokenType()===GlobalVar::TOKEN_TYPE_JWT){
            $jwt = new JWTService();
            $jwt->setSecret($secret);
            return $jwt->validate($this->getAuthorizationToken());
        }else{
            return parent::validate($secret);
        }
    }
    /**
     * 创建sign对象
     *
     * @param string $secret
     *
     * @return string
     */
    public static function createSign($secret, $params,$tokenType='KUGA')
    {
        if($tokenType===GlobalVar::TOKEN_TYPE_JWT){
            $jwt = new JWTService();
            $jwt->setSecret($secret);
            return $jwt->createToken($params);
        }else{
            return parent::createSign($secret,$params);
        }
    }
    public function getAccessToken()
    {
        return $this->getAuthorizationToken();
    }
    public function getAppKey()
    {
        return $this->_getHeader('Appkey');
    }
    public function getAppSecret(){
        return $this->_secret;
    }
    public function getVersion()
    {
        return $this->_getHeader('Version');
    }


    public function getLocale()
    {
        return $this->_getHeader('Locale');
    }
    public function getAccessTokenType()
    {
        return $this->_getHeader('Access-Token-Type')?strtoupper($this->_getHeader('Access-Token-Type')):'';
    }
    /**
 * 取得相关传参
 *
 * @return array
 */
    public function getParams()
    {
        $data = $this->_data;
        return $data;
    }
}