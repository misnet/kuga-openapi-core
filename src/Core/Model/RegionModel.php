<?php
namespace Kuga\Core\Model;
use Kuga\Core\Base\AbstractModel;

/**
 * 地区Model
 * Class RegionModel
 * @package Kuga\Core
 */
class RegionModel extends AbstractModel {

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 名称
     * @var String
     */
    public $name;

    /**
     * 上一级ID
     * @var Integer
     */
    public $parentId;

    /**
     * 邮编
     * @var integer
     */
    public $zipcode =0;
    /**
     * 排序
     * @var integer
     */

    public $sortIndex =0;


    public function getSource() {
        return 't_regions';
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {

        return  array (
            'id' => 'id',
            'name' => 'name',
            'parent_id' => 'parentId',
            'zipcode' => 'zipcode',
            'sort_index'=>'sortIndex'
        );

    }
}
