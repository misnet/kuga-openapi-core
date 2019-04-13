<?php

namespace Kuga\Core\File\Adapter;

use Kuga\Core\File\FileAdapter;

/**
 * 本地文件存储管理
 *
 * @author Donny
 */
class Local extends FileAdapter
{


    /**
     *
     * {@inheritDoc}
     * @see \Kuga\Service\File\FileInterface::getVoltThumbUrl()
     */
    public function getVoltThumbUrl($src, $width, $height, $option = '')
    {
        // return '"'.QING_BASEURL.'thumb.php?src=".rawurlencode('.$src.')."&w='.$width.'&h='.$height.'"';
        return rtrim($this->option['hostUrl'], '\/').'/thumb.php?src='.rawurlencode($src).'&w='.$width.'&h='.$height;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Kuga\Service\File\FileAdapter::upload()
     */
    public function upload($filePath, $fileRequire, $options = null)
    {
        $this->validate($filePath, $fileRequire);
        $object = ltrim($fileRequire->newFilename, '\/');
        $path   = [];
        if ($this->option['baseDir']) {
            $path[] = $this->option['baseDir'];
        }
        $path[] = $object;
        $object = join('/', $path);
        //建目录
        $dirname = dirname($object);
        if ( ! file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
        $content = file_get_contents($filePath);
        file_put_contents(rtrim($this->option['rootDir'], '\/').'/'.$object, $content);
        unset($content);

        return $this->option['hostUrl'].$object;
    }

    public function remove($url)
    {
        $path = str_ireplace($this->option['hostUrl'], $this->option['rootDir'], $url);
        if (file_exists($path)) {
            @unlink($path);
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
        $src = str_replace($this->option['hostUrl'], $this->option['rootDir'], $srcUrl);
        if ( ! $targetObject) {
            $targetObject = str_replace($this->option['hostUrl'], '', $srcUrl);
        }
        $targetObject = 'cp_'.$targetObject;
        $targetUrl    = $this->option['hostUrl'].'/'.$targetObject;
        $targetFile   = $this->option['rootDir'].'/'.$targetObject;
        $targetFile   = realpath($targetFile);
        @\mkdir(dirname($targetFile), 0700, true);
        @\copy($src, $targetFile);

        return $targetUrl;
    }
}