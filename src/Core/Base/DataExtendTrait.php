<?php
namespace Kuga\Core\Base;
trait  DataExtendTrait{

    /**
     * 是否删除
     * @var int 1是，0不是
     */
    public $isDeleted = 0;
    /**
     * 创建时间
     * @var int
     */
    public $createTime = 0;
    /**
     * 更新时间
     * @var int
     */
    public $updateTime = 0;

    public function extendColumnMapping(){
        return [
            'is_deleted'=>'isDeleted',
            'create_time'=>'createTime',
            'update_time'=>'updateTime'
        ];
    }
    public function beforeCreate(){
        $this->createTime||$this->createTime = time();
        $this->updateTime = $this->createTime;
        return true;
    }

    /**
     *
     * AbstractCatalogModel中有定义了beforeUpdate，用method_exists来判断不起作用
     * @return bool
     */
    public function beforeUpdate(){
        if(is_callable('parent::beforeUpdate')){
            try{
                parent::beforeUpdate();
            }catch(\Exception $e){

            }
        }
        $this->updateTime = time();
        return true;
    }
}
