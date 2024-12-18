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
    private $initConfigure = [];

    /**
     * options
     * @param $options
     */
    public function initOption($options)
    {
        $config = $options;
        if(!$options['bucket']){
            throw new ServiceException('bucket is required');
        }
        if(!$options['bucket']['name']){
            throw new ServiceException('bucket name is required');
        }
        if(!$options['bucket']['hostUrl']){
            throw new ServiceException('bucket hostUrl is required');
        }
        if(!$options['accessKeyId']){
            throw new ServiceException('AliyunOSS accessKeyId is required');
        }
        if(!$options['accessKeySecret']){
            throw new ServiceException('AliyunOSS accessKeySecret is required');
        }
        $this->option     = $options;
    }

    /**
     * 给对象设置标签
     * @param string $object
     * @param array $tags
     * @return void
     * @throws \OSS\Core\OssException
     */
    public function setTags($object,$tags=[]){
        $ossClient = new \OSS\OssClient(
            $this->option['accessKeyId'], $this->option['accessKeySecret'], $this->option['bucket']['endpoint']
        );
        $tagConfig  = new \OSS\Model\TaggingConfig();
        foreach($tags as $k=>$v) {
            $tag = new \OSS\Model\Tag($k, $v);
            $tagConfig->addTag($tag);
        }
        if(!empty($tagConfig->getTags())){
            $ossClient->putObjectTagging($this->option['bucket']['name'],$object,$tagConfig);
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
        $config = $this->_di->getShared('config');
        $ossClient = new \OSS\OssClient($this->option['accessKeyId'], $this->option['accessKeySecret'], $this->option['bucket']['endpoint']);
        $object    = str_replace($this->option['bucket']['hostUrl'].'/', '', $url);
        $ossClient->deleteObject($this->option['bucket']['name'], $object);

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