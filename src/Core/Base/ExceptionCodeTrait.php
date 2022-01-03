<?php
namespace Kuga\Core\Base;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;

trait ExceptionCodeTrait{

    public static $EXCODE_SUCCESS = 0;
    public static $EXCODE_UNKNOWN  = 99999;
    /**
     * 无效的appKey
     * @var int
     */
    public static $EXCODE_INVALID_CLIENT = 99000;
    /**
     * 签名错误
     * @var int
     */
    public static $EXCODE_ERROR_SIGN     = 99001;
    /**
     * 无效的API方法
     * @var int
     */
    public static $EXCODE_INVALID_METHOD = 99002;
    /**
     * 必要参数缺少
     * @var int
     */
    public static $EXCODE_PARAMMISS = 99003;

    /**
     * 无效Token
     * @var int
     */
    public static $EXCODE_INVALID_ACCESSTOKEN = 99004;

    /**
     * 记录不存在
     * @var int
     */
    public static $EXCODE_NOTEXIST = 99005;


    /**
     * 无效的刷新token
     * @var int
     */
    public static $EXCODE_INVALID_REFRESHTOKEN = 99006;
    /**
     * 无权限，禁止访问
     * @var
     */
    public static $EXCODE_FORBIDDEN = 99008;

    /**
     * 非所有者, 进行一些操作时，这些信息的所有者非当前用户，系统会禁止
     * @var int
     */
    public static $EXCODE_NOT_OWNER = 99007;

    private static $di;
    public static function setDi($di){
        self::$di = $di;
    }
    public static function getExceptionList(){
        return [];
    }

    public static function getAllExceptions(){
        if(!self::$di){
            self::$di = Di::getDefault();
        }
        $t = self::$di->get('translator');
        $data = array(
            self::$EXCODE_SUCCESS=>'',
            self::$EXCODE_UNKNOWN=>'',
            self::$EXCODE_NOTEXIST => $t->_('数据不存在'),
            self::$EXCODE_INVALID_ACCESSTOKEN => $t->_('Access Token无效'),
            self::$EXCODE_INVALID_REFRESHTOKEN => $t->_('Refresh Token无效'),
            self::$EXCODE_PARAMMISS => $t->_('参数缺失'),
            self::$EXCODE_INVALID_CLIENT=>$t->_('无效appkey或appsecret'),
            self::$EXCODE_ERROR_SIGN=>$t->_('无效签名'),
            self::$EXCODE_INVALID_METHOD=>$t->_('无效的接口'),
            self::$EXCODE_NOT_OWNER=>$t->_('非所有者'),
            self::$EXCODE_FORBIDDEN=>$t->_('当前用户无权限')
        );
        return $data + static::getExceptionList();
    }
    public static function getExMsg($code){
        $_data = self::getAllExceptions();
        return array_key_exists($code, $_data)?$_data[$code]:'';
    }
}