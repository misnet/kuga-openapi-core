<?php
/**
 * 配置文件
 * 注意：如果config/config.php文件存在，则本文件内容可以被config.php覆盖
 * @author Donny
 */
define('CONFIG_DIR',__DIR__);

$_CONFIG['dbwrite'] = array(
    'adapter' =>'mysql',
    'port'=>3306,
    'host'=>'localhost',
    'username'=>'root',
    'password'=>'',
    'dbname'=>''
);
$_CONFIG['dbread'] = $_CONFIG['dbwrite'];
$_CONFIG['weixin'] = array(
    'token'=>'',
    'appid'=>'',
    'appsecret'=>'',
    'notcheck'=>true
);
$_CONFIG['system']['charset']       = 'utf-8';
$_CONFIG['system']['locale']       = 'zh_CN';

//redis配置
$_CONFIG['redis']['host'] = 'localhost';
$_CONFIG['redis']['port'] = '6379';
$_CONFIG['redis']['password'] = '';
$_CONFIG['redis']['db'] = 0;
$_CONFIG['redis']['statsKey']  = 'KG';

//用的队列程序
$_CONFIG['queue']['adapter'] = 'redis';

//权限资源配置文件
$_CONFIG['acc'] = CONFIG_DIR.'/acc.xml';


$cache['cache']['slow']['engine'] = 'file';
$cache['slow']['option']['cacheDir'] = '/tmp';
$cache['slow']['option']['lifetime'] = 86400;
$cache['fast']['engine'] = 'redis';
$cache['fast']['option'] = $_CONFIG['redis'];
$_CONFIG['cache'] = $cache;


//文件存储配置，具体用哪一个在env.php中
$_CONFIG['fileStorage']['adapter'] = 'aliyun'; //值为aliyun或localfile
$_CONFIG['fileStorage']['localfile'] = [
    'hostUrl'=>'',
    'baseDir'=>'data',
    'rootDir'=>'',
];
$_CONFIG['fileStorage']['aliyun'] = [
    'configFile'=>CONFIG_DIR.'/aliyunoss/config.json',
    'policyFile'=>CONFIG_DIR.'/aliyunoss/policy-all.txt',
];

//短信配置
$_CONFIG['sms']['adapter']  = 'aliyun'; //采用腾讯的，要用阿里云的，改为aliyun
$_CONFIG['sms']['tencent']  = CONFIG_DIR.'/sms/tencent.config.json';
$_CONFIG['sms']['aliyun']   = CONFIG_DIR.'/sms/aliyun.config.json';

$_CONFIG['email']  = CONFIG_DIR.'/aliemail.config.json';

//session配置
$_CONFIG['session']= CONFIG_DIR.'/session.config.json';

//jwt secret key
$_CONFIG['jwtTokenSecret'] = 'jwt token secret';

//accessToken中用户标识的键值
$_CONFIG['accessTokenUserIdKey'] = 'uid';

//是否开启记录API访问日志
$_CONFIG['apiLogEnabled'] = false;

//多域名配置
//当访问a.xxx.com和访问api.xxx.com一样效果
//$_CONFIG['domainMapping'] = [
//    'api'=>'a'
//];
//测试模式
$_CONFIG['testmodel'] = true;

//调试模式，非预见性错误或程序错误时，api会显示debug信息
$_CONFIG['debug']     = true;
return $_CONFIG;
