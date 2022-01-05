<?php
/**
 * Kuga Openapi-SDK 初始化文件，调用示例：
 *
 * $customConfig = include('sample-config/config.default.php');
 * Kuga\Init::setTmpDir('/opt/tmp);
 * Kuga\Init::setup($customConfig);
 */

namespace Kuga;

class Init
{

    /**
     * @var \Phalcon\Di\DiInterface
     */
    static private $di;

    static private $config;

    static private $eventsManager;

    static private $tmpDir = '/tmp';


    public static function getDI()
    {
        return self::$di;
    }

    /**
     * 设置Temp目录
     * @param $t
     */
    public static function setTmpDir($t)
    {
        if (is_dir($t)) {
            self::$tmpDir = $t;
        }
        if (!file_exists(self::$tmpDir)) {
            mkdir(self::$tmpDir, '0777', true);
        }
        //metadata用到
        if (!file_exists(self::$tmpDir . '/meta')) {
            mkdir(self::$tmpDir . '/meta', 0777, true);
        }
    }

    /**
     * 取得临时目录
     * @return string
     */
    public static function getTmpDir()
    {
        return self::$tmpDir;
    }

    /**
     * 初始化系统
     *
     * 如果要更改临时目录，建议在运行setup之前运行setTmpDir();
     * @param array $config 配置数组
     * @param null $di
     */
    public static function setup($config = [], $di = null)
    {
        //json_encode 带有小数的数字时，会变成14位长的精确值，需要修改php.ini的serialize_precision值
        ini_set('serialize_precision', -1);
        self::$di = $di;
        if (!self::$di) {
            self::$di = \Phalcon\Di\FactoryDefault::getDefault();
        }
        self::$eventsManager = $di->getShared('eventsManager');
        self::$config = new \Phalcon\Config\Config($config);

        self::injectLoggerService();
        self::injectConfigService();
        self::injectCacheService();
        self::injectI18n();
        self::injectDatabase();
        self::injectSmsService();
        self::injectEmailService();
        self::injectCryptService();
        self::injectSimpleStorageService();
        self::injectFileStorageService();
        self::injectQueueService();
        self::injectSessionService();


        //增加插件
        self::$eventsManager = $di->getShared('eventsManager');
        self::$eventsManager->collectResponses(true);
        \Kuga\Core\Service\PluginManageService::init(self::$eventsManager, self::$di);
        \Kuga\Core\Service\PluginManageService::loadPlugins();
    }

    /**
     * Inject Logger Service
     */
    private static function injectLoggerService()
    {
        $tmpDir = self::$tmpDir;
        self::$di->set('logger', function () use ($tmpDir) {
            return \Phalcon\Logger\Factory::load(
                ['name' => $tmpDir . '/logger.txt', 'adapter' => 'file']
            );
        }, true
        );
    }

    /**
     * Inject Config Service
     */
    private static function injectConfigService()
    {
        $config = self::$config;
        self::$di->set(
            'config', function ($item = null) use ($config) {
            if (is_null($item) || !isset($config->{$item})) {
                return $config;
            } else {
                return $config->{$item};
            }
        }, true
        );
    }

    /**
     * Inject Cache Service
     */
    private static function injectCacheService()
    {
        $config = self::$config;
        //缓存对象纳入DI
        self::$di->set(
            'cache', function ($prefix = '') use ($config) {
            if (file_exists($config->cache)) {
                $cacheConfigContent = file_get_contents($config->cache);
                $option = \json_decode($cacheConfigContent, true);
                if (isset($option['slow']) && $prefix) {
                    $option['slow']['option']['prefix'] = $prefix;
                }
                if (isset($option['fast']) && $prefix) {
                    $option['fast']['option']['prefix'] = $prefix;
                }
                $cache = new \Qing\Lib\Cache($option);
                return $cache;
            } else {
                throw new \Exception('Cache file does not exists');
            }
        }, true
        );
    }

    /**
     * Inject I18n  Service
     * Need gettext php-extension
     */
    private static function injectI18n()
    {
        $di = self::$di;
        $config = self::$config;
        //翻译器
        self::$di->set(
            'translator', function () use ($di, $config) {
            $locale = $config->system->locale;

            if ($config->system->charset) {
                $locale .= '.' . $config->system->charset;
            }
            $directory['common'] = __DIR__ . '/langs/_common';
            $translator = new \Qing\Lib\Translator\Gettext(array(
                'locale' => $locale,
                'defaultDomain' => 'common',
                'category' => LC_MESSAGES,
                //'cache'         => self::$di->getShared('cache'),
                'cache' => null,
                'directory' => $directory
            ));
            return $translator;
        }, true
        );
    }

    /**
     * 创建数据库连接
     * @param $config
     * username
     * password
     * charset
     * dbname
     * port
     *
     * @return \Phalcon\Db\Adapter\Pdo\Mysql
     */
    public static function createDatabaseAdapter($config, $eventsManager = null)
    {
        $db = new \Phalcon\Db\Adapter\Pdo\Mysql(
            ['host' => $config->host, 'username' => $config->username, 'password' => $config->password,
                'port' => $config->port, 'dbname' => $config->dbname, 'charset' => $config->charset,
                'options' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET time_zone ="' . date('P') . '"'],
                'dialectClass' => self::initDialect()]
        );
        if (!$eventsManager) {
            $eventsManager = self::$eventsManager;
        }
        $db->setEventsManager($eventsManager);
        $db->getInternalHandler()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $db->getInternalHandler()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        return $db;
    }

    /**
     * 注入数据库服务
     */
    private static function injectDatabase()
    {
        $eventsManager = self::$eventsManager;
        $config = self::$config;
        $di = self::$di;
        $di->set(
            'dbRead', function () use ($config, $eventsManager) {
            $dbRead = self::createDatabaseAdapter($config->dbread, $eventsManager);
            return $dbRead;
        },true);
        $di->set('dbWrite', function () use ($config, $eventsManager) {
            $dbWrite = self::createDatabaseAdapter($config->dbwrite, $eventsManager);
            return $dbWrite;
        },true);

        //实现对model的meta缓存
        $di->set(
            "modelsCache", function () use ($di) {
            return $di->get('cache')->getCacheEngine();
        },true);
        self::$di['modelsMetadata'] = function () {
            $metaData = new \Phalcon\Mvc\Model\MetaData\Stream(
                ["lifetime" => 86400, "prefix" => "kuga", "metaDataDir" => self::$tmpDir . '/meta/']
            );
            return $metaData;
        };

        $di->set(
            'transactions', function () {
            $tm = new \Phalcon\Mvc\Model\Transaction\Manager();
            $tm->setDbService('dbWrite');
            return $tm;
        },true
        );

        //非空验证取消，否则当字段设定为not null时，虽有default值，但在model中如没指定值时，系统会报错
        \Phalcon\Mvc\Model::setup(
            ['notNullValidations' => false]
        );
        \Phalcon\Mvc\Model::setup(
            ['updateSnapshotOnSave' => false,]
        );

        if ($config->debug) {
            $adapter = new \Phalcon\Logger\Adapter\Stream(self::$tmpDir . "/db.log");
            $logger  = new \Phalcon\Logger\Logger('messages',['main'=>$adapter]);
            $eventsManager->attach('db', function ($event, $connection) use ($logger) {
                if ($event->getType() == 'beforeQuery') {
                    $logger->log(\Phalcon\Logger\Logger::INFO,$connection->getSQLStatement());
                    $logger->log(\Phalcon\Logger\Logger::INFO,print_r($connection->getSqlVariables(), true));
                }
            });
        }
    }

    /**
     * 注入短信服务
     */
    private static function injectSmsService()
    {
        $config = self::$config;
        $di = self::$di;
        self::$di->set(
            'sms', function () use ($config, $di) {
            $adapter = $config->sms->adapter;
            $smsAdapter = \Kuga\Core\Sms\SmsFactory::getAdapter($adapter, $config->sms->{$adapter}, $di);

            return $smsAdapter;
        },true);
    }

    private static function injectCryptService()
    {
        $config = self::$config;
        self::$di->set('crypt', function () use ($config) {
            $crypt = new \Phalcon\Encryption\Crypt();
            //Please use your private key
            $crypt->setKey(md5($config->system->copyright));
            return $crypt;
        },true);
    }

    /**
     * Inject Session Service
     */
    private static function injectSessionService()
    {
        $config = self::$config;
        if (file_exists($config->session)) {
            //读取配置
            $sessionConfigContent = file_get_contents($config->session);
            $sessonConfig = \json_decode($sessionConfigContent, true);
            if ($sessonConfig && $sessonConfig['enabled']) {
                $adapter = $sessonConfig['adapter'];
                $sessionOption = is_array($sessonConfig['option']) ? $sessonConfig['option'] : [];
                if ($sessionOption) {
                    if ($adapter == 'redis') {
                        $session = new \Phalcon\Session\Adapter\Redis($sessionOption);
                        $option = $config->redis;
                        $option = \Qing\Lib\utils::arrayExtend($option, $sessionOption);
                    } else {
                        $session = new \Phalcon\Session\Adapter\Files($sessionOption);
                        $option = $sessionOption;
                    }
                    self::$di->set('session', function () use ($option, $session) {
                        if (isset($_POST['sessid'])) {
                            session_id($_POST['sessid']);
                        }
                        //$session = new \Phalcon\Session\Adapter\Redis($option);
                        ini_set('session.cookie_domain', \Qing\Lib\Application::getCookieDomain());
                        ini_set('session.cookie_path', '/');
                        ini_set('session.cookie_lifetime', 86400);
                        $session->start();
                        return $session;
                    },true);
                }
            }
        }
    }

    /**
     * 简单存储器
     */
    private static function injectSimpleStorageService()
    {
        //NOSQL简单存储器
        $config = self::$config;
        self::$di->set('simpleStorage', function () use ($config) {
            if (file_exists($config->cache)) {
                $cacheConfigContent = file_get_contents($config->cache);
                $option = \json_decode($cacheConfigContent, true);
                if (strtolower($option['fast']['engine']) == 'redis') {
                    return new \Qing\Lib\SimpleStorage($option['fast']['option']);
                } else {
                    throw new \Exception('redis config does not exists');
                }
            } else {
                throw new \Exception('Cache file does not exists');
            }

        });
    }

    /**
     * 注入文件存储服务
     */
    private static function injectFileStorageService()
    {

        $config = self::$config;
        $di = self::$di;
        self::$di->set('fileStorage', function () use ($config, $di) {
            $adapterName = $config->fileStorage->adapter;
            $option = $config->fileStorage->{$adapterName};
            return \Kuga\Core\Service\FileService::factory($adapterName, $option, $di);
        },true);
    }

    /**
     * 注入队列服务
     */
    private static function injectQueueService()
    {
        //队例对象
        $config = self::$config;
        $di = self::$di;
        self::$di->set('queue', function () use ($config, $di) {
            $redisConfig = $config->redis;
            $redisAdapter = new \Qing\Lib\Queue\Adapter\Redis($redisConfig);
            $queue = new \Qing\Lib\Queue();
            $queue->setAdapter($redisAdapter);
            $queue->setDI($di);
            return $queue;
        },true);
    }

    /**
     * 注入发邮件服务
     */
    private static function injectEmailService()
    {
        $di = self::$di;
        $config = self::$config;
        $di->set('emailer', function () use ($config, $di) {
            return new \Kuga\Core\Service\AliEmail($config->email, $di);
        },true);
    }

    private static function initDialect()
    {
        $dialect = new \Phalcon\Db\Dialect\Mysql();
        $dialect->registerCustomFunction('group_concat_orderby', function ($dialect, $expression) {
            $arguments = $expression['arguments'];
            return sprintf(" group_concat(%s order by %s asc SEPARATOR %s) ",
                $dialect->getSqlExpression($arguments[0]),
                $dialect->getSqlExpression($arguments[1]),
                $dialect->getSqlExpression($arguments[2]));
        });
        return $dialect;
    }
}
