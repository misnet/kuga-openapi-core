<?php
/**
 * Abstract Catalog Model
 * @author Donny
 */

namespace Kuga\Core\Base;
use Kuga\Core\Api\Exception;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;
use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;

class AbstractCatalogModel extends AbstractModel
{
    public $id;
    public $name;
    public $parentId;
    public $leftPosition  = 0;
    public $rightPosition = 0;
    public $sortWeight = 0;
    /**
     * 删除时候是否要删除整树
     *
     * @var boolean
     */
    protected $_delTree = false;
    public function columnMap()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'parent_id' => 'parentId',
            'left_position'=>'leftPosition',
            'right_position' =>'rightPosition',
            'sort_weight'=>'sortWeight'
        ];
    }
    public function initialize()
    {
        parent::initialize();
        $this->keepSnapshots(true);
    }

    public function beforeCreate(){
        $this->leftPosition = $this->rightPosition = 0;
        return true;
    }

    public function beforeUpdate(){
        if($this->id==$this->parentId){
            throw new Exception($this->translator->_('上级类目不可以是自己'));
        }
        $snapshotData = $this->getSnapshotData();
        if ($this->parentId && $snapshotData['parentId'] != $this->parentId) {
            $targetParentNode = $this->findFirst(array(
                'id' => $this->parentId
            ));
            if ($targetParentNode->leftPosition > $snapshotData['leftPosition'] && $targetParentNode->rightPosition < $snapshotData['rightPosition']) {
                throw new Exception($this->translator->_('上一级节点是当前节点的子孙节点，不可以修改'));
            }
        }
    }

    public function afterCreate(){
        $row = self::findFirst([
            'parentId=:pid: and id!=:id: and sortWeight>=:dw:',
            'bind'=>['pid'=>$this->parentId, 'id'=>$this->id, 'dw'=>$this->sortWeight],
            'order'=>'sortWeight asc,leftPosition desc'
        ]);
        if($row){
            $right = $row->rightPosition;
        }else{
            $row   = $this->findFirstById($this->parentId);
            if($row){
                $right = $row->leftPosition;
            }else{
                $right = 0;
            }
        }
        $sql = 'update ' . $this->getSource() . ' set right_position = right_position +2 where right_position > ?';
        $this->getWriteConnection()->execute($sql, array(
            $right
        ));
        $sql = 'update ' . $this->getSource() . ' set left_position = left_position + 2 where left_position > ?';
        $this->getWriteConnection()->execute($sql, array(
            $right
        ));
        $this->leftPosition  = $right + 1;
        $this->rightPosition = $right + 2;
        $this->update();
    }
    public function afterUpdate(){
        $snapshotData = $this->getSnapshotData();
        $width = $snapshotData['rightPosition'] - $snapshotData['leftPosition'] + 1;
        $right = $snapshotData['rightPosition'];
        $left  = $snapshotData['leftPosition'];
        // 插入节点到相应位置
        $row = $this->findFirst(array(
            'conditions' => 'parentId=?1 and id!=?2 and sortWeight>=?3',
            'bind' => array(
                1 => $this->parentId,
                2 => $this->id,
                $this->sortWeight
            ),
            'order' => 'sortWeight asc,leftPosition desc'
        ));
        if ($row) {
            $afterUpdateRight = $row->rightPosition;
        } else {
            $row = $this->findFirst(array(
                'conditions' => 'id=?1 ',
                'bind' => array(
                    1 => $this->parentId
                )
            ));
            if ($row) {
                $afterUpdateRight = $row->leftPosition;
            } else {
                $afterUpdateRight = 0;
            }
        }
        if ($afterUpdateRight < $left) {
            // 子树先重置lt和rt，并变为负数
            $betaLeft = $left - $afterUpdateRight - 1;
            $sql = 'update ' . $this->getSource() . ' set right_position = (right_position - ?) * -1,left_position=(left_position - ?)*-1 where right_position <= ? and left_position>=?';
            $this->getWriteConnection()->execute($sql, array(
                $betaLeft,
                $betaLeft,
                $right,
                $left
            ));

            $sql = 'update ' . $this->getSource() . ' set right_position = right_position + ?  where right_position >? and right_position <?';
            $this->getWriteConnection()->execute($sql, array(
                $width,
                $afterUpdateRight,
                $left
            ));

            $sql = 'update ' . $this->getSource() . ' set left_position = left_position + ?  where left_position > ? and left_position <?';
            $this->getWriteConnection()->execute($sql, array(
                $width,
                $afterUpdateRight,
                $left
            ));

            $sql = 'update ' . $this->getSource() . ' set right_position = right_position * -1,left_position= left_position * -1 where right_position <0 and left_position<0';
            $this->getWriteConnection()->execute($sql);
        } else {
            // 将要移动的节点标志出来——标负
            $sql = 'update ' . $this->getSource() . ' set right_position = right_position *-1  ,left_position=left_position *-1 where right_position <= ? and left_position>=?';
            $this->getWriteConnection()->execute($sql, array(
                $right,
                $left
            ));

            // 父级变化就要降宽度
            // if($snapshotData['parentId']!=$this->parentId){
            // $sql = 'update '.$this->getSource().' set right_position = right_position - ? where parent_id=?';
            // $this->getWriteConnection()->execute($sql,array($width,$snapshotData['parentId']));
            // }
            // 插入点至右边的点要移动
            $sql = 'update ' . $this->getSource() . ' set right_position = right_position - ?  where right_position > ? and right_position <=?';
            $this->getWriteConnection()->execute($sql, array(
                $width,
                $right,
                $afterUpdateRight
            ));
            $sql = 'update ' . $this->getSource() . ' set left_position = left_position - ?  where left_position > ? and left_position <=?';
            $this->getWriteConnection()->execute($sql, array(
                $width,
                $right,
                $afterUpdateRight
            ));
            // 增量
            $addPosition = $afterUpdateRight - $width + 1 - $left;

            // 移动的点恢复正数
            $sql = 'update ' . $this->getSource() . ' set right_position = (right_position - ?) * -1,left_position= (left_position -?) * -1  where right_position <0 and left_position<0';
            $this->getWriteConnection()->execute($sql, array(
                $addPosition,
                $addPosition
            ));
        }
    }
    private function deleteTree($lft,$rgt){
        $width = $rgt - $lft + 1;
        $right = $rgt;
        $left  = $lft;
        $sql = 'delete from ' . $this->getSource() . ' where left_position between ? and ?';
        $this->getWriteConnection()->execute($sql, array(
            $left,
            $right
        ));
        $sql = 'update ' . $this->getSource() . ' set right_position = right_position - ? where right_position > ?';
        $this->getWriteConnection()->execute($sql, array(
            $width,
            $right
        ));
        $sql = 'update ' . $this->getSource() . ' set left_position = left_position - ? where left_position > ?';
        $this->getWriteConnection()->execute($sql, array(
            $width,
            $right
        ));
    }
    public function afterDelete(){
        if ($this->_delTree) {
            $this->deleteTree($this->leftPosition,$this->rightPosition);
        } else {
            $right = $this->rightPosition;
            $left = $this->leftPosition;

            $sql = 'update ' . $this->getSource() . ' set right_position = right_position-1,left_position=left_position-1 where left_position between  ? and ?';
            $this->getWriteConnection()->execute($sql, array(
                $left,
                $right
            ));

            $sql = 'update ' . $this->getSource() . ' set right_position = right_position-2 where right_position > ?';
            $this->getWriteConnection()->execute($sql, array(
                $right
            ));
            $sql = 'update ' . $this->getSource() . ' set left_position = left_position-2 where left_position > ?';
            $this->getWriteConnection()->execute($sql, array(
                $right
            ));

            // 原子节点的父id改为当前节点的父id
            $sql = 'update ' . $this->getSource() . ' set parent_id = ? where parent_id = ?';
            $this->getWriteConnection()->execute($sql, array(
                $this->parentId,
                $this->id
            ));
        }
    }

}