<?php

namespace Kuga\Core\File;

use Kuga\Core\Base\AbstractService;
use Kuga\Core\File\FileInterface;
use Kuga\Core\Base\ServiceException;

abstract class FileAdapter extends AbstractService implements FileInterface
{

    /**
     * 配置项
     *
     * @var \Phalcon\Config
     */
    protected $option;

    /**
     * 初始化选项
     * @param $options
     */
    public function initOption($options){
        $this->option = $options;
    }

    public function setOption($key, $value)
    {
        if (array_key_exists($key, $this->option)) {
            $this->option[$key] = $value;
        }
    }

    protected function validate($filePath, $fileRequire)
    {
        $filesize = filesize($filePath);
        $t        = $this->translator;
        $mimetype = mime_content_type($filePath);
        if ($fileRequire->maxFilesize && $filesize > $fileRequire->maxFilesize) {
            throw new ServiceException(
                $t->_('文件大小超过%filesize%', ['filesize' => \Qing\Lib\Utils::generateFileSize($fileRequire->maxFilesize)])
            );
        }
        if ($fileRequire->mimeTypePattern && ! preg_match($fileRequire->mimeTypePattern, $mimetype)) {
            throw new ServiceException($t->_('不支持此类型文件上传'));
        }

        return true;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Kuga\Service\File\FileInterface::upload()
     */
    public function upload($filePath, $fileRequire, $options = null)
    {
    }

    public function remove($url)
    {
    }

    public function copy($url, $target = '')
    {
    }

    public function getImageInfo($src)
    {
    }

    public function getOption()
    {
        return $this->option;
    }

    public function getBaseUrl()
    {
        $path[] = rtrim($this->option['hostUrl'], '\/');
        if (isset($this->option['baseDir']) && $this->option['baseDir']) {
            $path[] = rtrim($this->option['baseDir'], '\/');
        }

        return join('/', $path).'/';
    }
}