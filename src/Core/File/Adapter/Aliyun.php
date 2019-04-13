<?php

namespace Kuga\Core\File\Adapter;

use Kuga\Core\Base\ServiceException;
use \Kuga\Core\File\FileAdapter;

class Aliyun extends FileAdapter
{

    /**
     * 开发模式下参数
     *
     * @var array
     */
    private $developEvnOption = [];

    /**
     * 正式环境下参数
     *
     * @var array
     */
    private $productionEnvOption = [];

    /**
     * options有两项configFile和policyFile
     * @param $options
     */
    public function initOption($options)
    {
        $config = $options;
        if (file_exists($config['configFile'])) {
            $configContent = file_get_contents($config['configFile']);
            $configJson    = json_decode($configContent, true);
            $key     = 'Bucket';
            $testKey = 'TestBucket';

            $option['accessKeyId']     = $configJson['AccessKeyId'];
            $option['accessKeySecret'] = $configJson['AccessKeySecret'];
            $option['roleArn']         = $configJson['RoleArn'];
            $option['tokenExpireTime'] = $configJson['TokenExpireTime'];
            $option['roleSessionName'] = $configJson['RoleSessionName'];

            $option['bucket']['endpoint']        = $configJson[$key]['Endpoint'];
            $option['bucket']['name']            = $configJson[$key]['Name'];
            $option['bucket']['hostUrl']         = $configJson[$key]['Host'];
            $option['bucket']['region']          = $configJson[$key]['Region'];
            if (file_exists($config['policyFile'])) {
                $option['policy'] = file_get_contents($config['policyFile']);
            }
            $this->option              = $option;
            $this->productionEnvOption = $option;
            if (isset($configJson[$testKey])) {
                $testOption             = $option;
                $testOption['bucket']['endpoint'] = $configJson[$testKey]['Endpoint'];
                $testOption['bucket']['bucket']   = $configJson[$testKey]['Name'];
                $testOption['bucket']['hostUrl']  = $configJson[$testKey]['Host'];
                $testOption['bucket']['region']   = $configJson[$testKey]['Region'];
                $this->developEvnOption = $testOption;
            }
            //测试|开发模式下用测试的选项
            $conf = $this->_di->getShared('config');
            if ($conf->testmodel && ! empty($this->developEvnOption)) {
                $this->option = $this->developEvnOption;
            }
        }else{
            throw new ServiceException($this->translator->_('aliyun oss config file not exists'));
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see FileAdapter::upload()
     *
     */
    public function upload($filePath, $fileRequire, $options = null)
    {
        $content = file_get_contents($filePath);
        $object  = $fileRequire->newFilename;
        $this->validate($filePath, $fileRequire);
        $ossClient = new \OSS\OssClient(
            $this->option['accessKeyId'], $this->option['accessKeySecret'], $this->option['bucket']['endpoint']
        );
        $ossClient->putObject($this->option['bucket']['name'], $object, $content, $options);

        return $this->option['bucket']['hostUrl'].'/'.$object;
    }

    /**
     * 缩略图网址
     *
     * @param unknown $src
     * @param unknown $width
     * @param unknown $height
     *
     * @return string
     */
    public function getVoltThumbUrl($src, $width, $height, $fit = '')
    {
        //return $src.'."@'.$width.'w_'.$height.'h_2e"';
        if ($fit === '') {
            $option = '';
        } else {
            //TODO:
            $option = ',m_'.$fit;
        }

        if (preg_match('/.*(aliyuncs.com).*/', $src)) {
            return $src.'?x-oss-process=image/resize,w_'.$width.',h_'.$height.$option;
        } else {
            return '';
        }
    }

    public function getImageInfo($src)
    {
        $data = file_get_contents($src.'@infoexif');
        $info = json_decode($data, true);
        if ($info) {
            return ['width' => $info['ImageWidth']['value'], 'height' => $info['ImageHeight']['value'], 'extension' => $info['Format']['value']];
        } else {
            return [];
        }
    }

    /**
     * 删除文件
     * {@inheritDoc}
     *
     * @see \Kuga\Service\File\FileAdapter::remove()
     */
    public function remove($url)
    {
        $config = $this->di->getShared('config');
        //非测试环境下可以删除这些对象
        //测试环境下，当指定的测试bucket和正式的不一样，也可以删除
        if ( ! $config->testmodel || ($this->developEvnOption && $this->developEvnOption['bucket']['name'] != $this->productionEnvOption['bucket']['name'])) {
            $ossClient = new \OSS\OssClient($this->option['accessKeyId'], $this->option['accessKeySecret'], $this->option['bucket']['endpoint']);
            $object    = str_replace($this->option['bucket']['hostUrl'].'/', '', $url);
            $ossClient->deleteObject($this->option['bucket']['name'], $object);
        }

    }

    /**
     * 复制对象
     *
     * @param        $srcUrl
     * @param string $targetObject
     *
     * @return string
     */
    public function copy($srcUrl, $targetObject = '')
    {
        //require_once QING_ROOT_PATH.'/aliyun-oss-php-sdk-2.2.2.phar';
        $ossClient = new \OSS\OssClient(
            $this->option['accessKeyId'], $this->option['accessKeySecret'], $this->option['bucket']['endpoint']
        );
        $src       = preg_replace(
            '/^(https|http):\/\/([0-9a-zA-Z\-_]{1,}).([0-9a-zA-Z\-_.]{1,}).aliyuncs.com\/(.*)$/is', '$4', $srcUrl
        );
        $srcBucket = preg_replace(
            '/^(https|http):\/\/([0-9a-zA-Z\-_]{1,}).([0-9a-zA-Z\-_.]{1,}).aliyuncs.com\/(.*)$/is', '$2', $srcUrl
        );
        if ( ! $targetObject) {
            $targetObject = $src;
        }
        $targetObject = 'cp_'.$targetObject;
        $ossClient->copyObject($srcBucket, $src, $this->option['bucket']['name'], $targetObject);

        return $this->option['bucket']['hostUrl'].'/'.$targetObject;
    }
}