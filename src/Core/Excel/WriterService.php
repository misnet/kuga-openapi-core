<?php

namespace Kuga\Core\Excel;

use Kuga\Core\Base\AbstractService;
use Kuga\Core\File\FileRequire;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Font;

/**
 * Excel 创建类
 * Class WriterService
 * @package Kuga\Core\Excel
 */
class WriterService extends AbstractService
{
    private $columnList = [];
    private $dataList = [];

    public $trueTypeFontPath = '';
    public $fontName = 'Verdana';
    public $headBackgroundColor = 'FFCCCCCC';
    public $borderColor = 'FF000000';
    public $fontSize = 12;
    public $titleFontSize = 16;
    public $title = '';
    public $titleRowHeight = 30;
    /**
     * 数据开始行，1开始
     * @var int
     */
    private $startLine = 1;
    /**
     * 数据起始列，1开始
     * @var int
     */
    private $startColumnIndex = 1;
    private $filename;

    public function resetColumn()
    {
        $this->columnList = [];
        return $this;
    }

    /**
     * 增加列
     * @param Column $column
     */
    public function addColumn(Column $column)
    {
        $this->columnList[] = $column;
        return $this;
    }

    /**
     * 设置数据列表
     * @param $list
     * @return $this
     */
    public function setDataList($list)
    {
        $this->dataList = $list;
        return $this;
    }

    public function save($filename)
    {
        $this->filename = $filename;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $startLine = $this->startLine;
        $columnIndex = $this->startColumnIndex;
        $startColumnIndex = $columnIndex;
        $line = $startLine;
        if ($this->title) {
            $col = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($col . $line, $this->title);
            $line++;
        }
        foreach ($this->columnList as $column) {
            $col = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($col . $line, $column->title);
            if ($column->width == 'auto')
                $sheet->getColumnDimension($col)->setAutoSize(true);
            else
                $sheet->getColumnDimension($col)->setWidth($column->width);
            $columnIndex++;
        }
        $highestColumn = $sheet->getHighestColumn();
        $headLine = $startLine;
        if ($this->title) {
            $startCol = Coordinate::stringFromColumnIndex($this->startColumnIndex);
            $titleStyle = $sheet->getStyle($startCol . $this->startLine . ':' . $highestColumn . $this->startLine);
            $sheet->mergeCells($startCol . $this->startLine . ':' . $highestColumn . $this->startLine);
            $titleFont = $titleStyle->getFont();
            $titleFont->setSize($this->titleFontSize);
            $sheet->getRowDimension($startLine)->setRowHeight($this->titleRowHeight);
            $titleStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $headLine++;
        }

        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        //自动宽
        Font::setAutoSizeMethod(Font::AUTOSIZE_METHOD_EXACT);
        Font::setTrueTypeFontPath($this->trueTypeFontPath);

        $headStyle = $sheet->getStyle(Coordinate::stringFromColumnIndex($startColumnIndex) . $headLine . ':' . $highestColumn . $headLine);
        $headStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($this->headBackgroundColor);

        if ($this->dataList) {
            $line++;
            $index = 1;
            foreach ($this->dataList as $item) {
                $columnIndex = 1;
                foreach ($this->columnList as $column) {
                    $dataType = $column->dataType;
                    $cellValue = '';
                    $key = $column->key;
                    if (isset($item[$key])) {
                        $cellValue = $column->getValue($item[$key], $item);
                    }
                    switch ($key) {
                        case 'oid':
                            $cellValue = $index;
                            $dataType = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
                            break;
                        default:
                            ;
                    }
                    $col = Coordinate::stringFromColumnIndex($columnIndex);
                    $sheet->setCellValueExplicit($col . $line, $cellValue, $dataType);
                    $columnIndex++;
                }
                $line++;
                $index++;
            }
        }
        $line--;
        $startCol = Coordinate::stringFromColumnIndex($startColumnIndex);
        $bodyStyle = $sheet->getStyle($startCol . $startLine . ':' . $highestColumn . $line);
        $objFontA1 = $bodyStyle->getFont();
        $objFontA1->setSize($this->fontSize);
        $objFontA1->setName($this->fontName);
        //加边框
        $styleArray = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => $this->borderColor)
                )
            )
        );
        //垂直居中
        $bodyStyle->applyFromArray($styleArray);
        $bodyStyle->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
    }

    /**
     * 上传到云端服务器，并返回网址
     * @param string $savePath 保存路径
     * @param int $maxFilesize 最大控制文件大小，单位MB，默认值：10000
     * @return mixed
     */
    public function uploadToNetworkStorage($savePath='',$maxFilesize=10000){
        $fr = new FileRequire();
        $fr->maxFilesize = $maxFilesize*1024*1024;
        $filename = basename($this->filename);
        $fr->newFilename = $savePath.'/'.$filename;
        $url = $this->_di->getShared('fileStorage')->upload($this->filename,$fr);
        return $url;
    }
}