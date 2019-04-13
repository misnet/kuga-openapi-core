<?php

namespace Kuga\Core\Sms;

Interface SmsInterface
{

    /**
     * 发送短信
     *
     * @param        $to         接收手机号
     * @param        $tplId      模板ID
     * @param string $params     参数
     * @param array  $extendInfo 扩展信息
     * @param string $logMsg     日志记录信息
     *
     * @return mixed
     */
    public function send($to, $tplId, $params = '', $extendInfo = [], $logMsg = '');

    /**
     * @param      $configFile
     * @param null $di
     *
     * @return mixed
     */
    public function __construct($configFile, $di = null);

    public function getConfig();

    /**
     * 验证码
     *
     * @param string $to   手机号
     * @param string $code 验证码
     *
     * @return boolean
     */
    public function verifyCode($to, $code);

    /**
     * 发注册用验证码
     *
     * @param unknown $to
     * @param unknown $code
     * @param array   $extendInfo
     *
     * @return boolean
     */
    public function registerCode($to, $code, $extendInfo = []);

}