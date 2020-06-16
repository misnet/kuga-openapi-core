<?php
namespace Kuga\Core\Api\Request;
interface RequestInterface{
    public function getMethod();
    public function getAccessToken();

    public function getAccessTokenType();
    public function getAppKey();
    public function getAppSecret();
    public function getVersion();

    public function getSign();

    public function getLocale();

    /**
     * 取得相关传参
     *
     * @return array
     */
    public function getParams();

    /**
     * 根据Secret验证请求是否有效
     *
     * @param String $secret appSecret
     *
     * @return boolean
     */
    public function validate($secret);

    /**
     * 创建sign对象
     *
     * @param string $secret
     *
     * @return string
     */
    public static function createSign($secret, $params);

    /**
     * 取得显示格式
     *
     * @return string
     */
    public function getFormat();


    public function setOrigRequest($req);

    public function getOrigRequest();

    public function getData();
    /**
     * @param array $h
     */
    public function setHeaders($h);
    public function getHeaders();
    public function setMethod($s);
}