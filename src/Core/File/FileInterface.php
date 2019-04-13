<?php

namespace Kuga\Core\File;

interface FileInterface
{

    /**
     *
     * @param String                         $filePath
     * @param \Kuga\Service\File\FileRequire $fileRequire
     *
     * @return String 上传完后URL地址
     */
    public function upload($filePath, $fileRequire, $options = null);

    /**
     * 按Volt模板函数要求生成缩略url的字串
     *
     * @param String  $src    源图地址
     * @param integer $width  宽
     * @param integer $height 高
     */
    public function getVoltThumbUrl($src, $width, $height);

    public function remove($url);

    public function copy($url, $target = '');

    public function getImageInfo($src);

    public function getOption();
}