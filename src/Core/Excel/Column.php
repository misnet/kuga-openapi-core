<?php

namespace Kuga\Core\Excel;
/**
 * Excel的列
 * 基于PhpSpreadsheet
 * Class Column
 * @package Kuga\Core\Excel
 */
class Column {

    const TYPE_STRING  = 'str';
    const TYPE_NUMERIC = 'n';
    const TYPE_FORMULA = 'f';
    /**
     * 列对应数据的key
     * @var string
     */
    public $key;
    /**
     * 表头列名
     * @var string
     */
    public $title;
    /**
     * 列宽，auto表示自动列宽
     * @var string|integer
     */
    public $width = 'auto';
    /**
     * 列数据类型，对应\PhpOffice\PhpSpreadsheet\Cell\DataType的几种数据类型
     * @var string
     */
    public $dataType = 'str';

    private $exchangeFunction;

    public function setExchangeFunction($cb){
        $this->exchangeFunction = $cb;
    }

    /**
     * @param $propData
     * @param $lineData
     */
    public function getValue($value,$lineData){
        $v = $value;
        if($this->exchangeFunction){
            $v = call_user_func($this->exchangeFunction,$value,$lineData);
        }
        return $v;
    }
}