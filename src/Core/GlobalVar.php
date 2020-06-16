<?php
/**
 * 全局系统的相关参数配置
 * @author Donny
 *
 */
namespace Kuga\Core;
class GlobalVar{
    /**
     * 中国的国家电话区号
     * @var integer
     */
    const COUNTRY_CODE_CHINA = 86;
    /**
     * 测试模式下的验证码
     * @var integer
     */
    const VERIFY_CODE_FORTEST = 8888;
    /**
     * 取列表值时，limit最大值
     * @var integer
     */
    const DATA_MAX_LIMIT   = 300;
    /**
     * 取列表值时，limit默认值
     * @var integer
     */
    const DATA_DEFAULT_LIMIT   = 10;
    /**
     * 前端数据 10分钟缓存
     */
    const DATA_CACHE_LIFETIME = 600;
    /**
     * APP应用列表缓存ID
     */
    const APPLIST_CACHE_ID = 'appKeys';
    const TOKEN_TYPE_KUGA = 'KUGA';
    const TOKEN_TYPE_JWT  = 'JWT';

}