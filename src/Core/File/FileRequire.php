<?php
namespace Kuga\Core\File;
class FileRequire{
    /**
     * 最大文件大小要求
     * @var unknown
     */
    public $maxFilesize = 5*1024*1024;
    /**
     * 
     * @var string
     */
    public $newFilename    = '';
    /**
     * 文件类型要求
     * @var string
     */
    public $mimeTypePattern='';
    public function setImageMimeTypePattern(){
        $this->mimeTypePattern = '/^image\/(jpeg|png|jpg|gif)$/is';
    }
}