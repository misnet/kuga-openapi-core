<?php
/**
 * API Request Object
 *
 * @author Donny
 */

namespace Kuga\Core\Api;

class Request
{

    private $_data;
    private $_secret;
    /**
     *
     * @var \Phalcon\Http\Request
     */
    private $_origRequest;

    public function getMethod()
    {
        return $this->_get('method');
    }

    public function getAccessToken()
    {
        return $this->_get('access_token');
    }

    public function getAppKey()
    {
        return $this->_get('appkey');
    }
    public function getAppSecret(){
        return $this->_secret;
    }
    public function getVersion()
    {
        return $this->_get('version');
    }

    public function getSign()
    {
        return $this->_get('sign');
    }

    public function getLocale()
    {
        return $this->_get('locale');
    }

    /**
     * 取得相关传参
     *
     * @return array
     */
    public function getParams()
    {
        $data = $this->_data;

        //$data = $this->_unset('access_token', $data);
        $data = $this->_unset('method', $data);
        $data = $this->_unset('appkey', $data);
        $data = $this->_unset('format', $data);
        $data = $this->_unset('sign', $data);
        $data = $this->_unset('locale', $data);
        $data = $this->_unset('version', $data);
        return $data;
    }

    /**
     * 根据Secret验证请求是否有效
     *
     * @param String $secret appSecret
     *
     * @return boolean
     */
    public function validate($secret)
    {
        $this->_secret = $secret;
        $requestSign = $this->getSign();
        $data        = $this->_data;

        $data = $this->_unset('sign', $data);
        $sign = self::createSign($secret, $data);
        return $sign === $requestSign;
    }

    /**
     * 创建sign对象
     *
     * @param string $secret
     *
     * @return string
     */
    public static function createSign($secret, $params)
    {
        if ( ! is_array($params)) {
            $params = [];
        }
        $sign = $secret;
        ksort($params);
        foreach ($params as $k => $v) {
            if($v === null){
                continue;
            }
            if ( ! is_array($v) && ! is_object($v)) {
                $sign .= $k.$v;
            }else{
                $sign .= $k.json_encode($v,JSON_UNESCAPED_UNICODE);
            }
        }
        $sign .= $secret;
        return strtoupper(md5($sign));
    }

    /**
     * 取得显示格式
     *
     * @return string
     */
    public function getFormat()
    {
        $format = $this->_get('format');
        if ( ! $format) {
            $format = 'json';
        }

        return $format;
    }

    public function __construct($data)
    {
        $this->_data = $data;
    }

    private function _unset($name, $data)
    {
        if (isset($data[$name])) {
            unset($data[$name]);
        }

        return $data;
    }

    private function _get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    public function setOrigRequest($req)
    {
        $this->_origRequest = $req;
    }

    public function getOrigRequest()
    {
        return $this->_origRequest;
    }

    public function getData()
    {
        return $this->_data;
    }
}
