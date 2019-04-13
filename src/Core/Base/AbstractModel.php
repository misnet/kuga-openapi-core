<?php
/**
 * 基础Model
 *
 */
namespace Kuga\Core\Base;
use Kuga\Core\Base\ModelConditionObject as ModelCondition;

abstract class AbstractModel extends \Phalcon\Mvc\Model{
    use SmartFinderTrait;
    use StatsTrait;
    /**
     *
     * @var  \Qing\Lib\Translator\Gettext
     */
    protected  $translator;
    /**
     * 这里的$_belongToRelation必须用静态，否则在有调用Criteria::fromInput的地方时，getBelongToRelation会莫名其妙返回空值
     * @var unknown
     */
    protected static $_belongToRelation;
    public function getBelongToRelation(){
        return self::$_belongToRelation;
    }

    protected  static $_relations;

    public function getRelations(){
        return isset(self::$_relations[get_called_class()])?self::$_relations[get_called_class()]:array();
    }
    public function onConstruct(){
        $this->initStorage();
        if(!$this->translator){
            $this->translator    = $this->getDI()->getShared('translator');
        }
    }
    public function initialize(){
        $this->keepSnapshots(true);
        $this->setReadConnectionService('dbRead');
        $this->setWriteConnectionService('dbWrite');
        $this->setEventsManager($this->getDI()->getShared('eventsManager'));


        if(!$this->translator)
            $this->translator    = $this->getDI()->getShared('translator');
    }
    public function hasOne($fi,$rt,$rf,$op=array()){
        $namespace = __NAMESPACE__;
        if(isset($op['namespace'])){
            $namespace = $op['namespace'];
        }
        parent::hasOne($fi, $namespace."\\".$rt, $rf,$op);
        self::$_relations[get_called_class()][$fi]=array('model'=>$namespace."\\".$rt,'id'=>$rf);
        if(isset($op['join'])){
            self::$_relations[get_called_class()][$fi]['join'] = $op['join'];
        }else{
            self::$_relations[get_called_class()][$fi]['join'] = 'left';
        }
        if(isset($op['talias'])){
            self::$_relations[get_called_class()][$fi]['talias'] = $op['talias'];
        }
    }

    public function belongsTo($fi,$rt,$rf,$op=array()){
        $namespace = __NAMESPACE__;
        if(isset($op['namespace'])){
            $namespace = $op['namespace'];
        }
        parent::belongsTo($fi, $namespace."\\".$rt, $rf,$op);
        self::$_relations[get_called_class()][$fi]=array('model'=>$namespace."\\".$rt,'id'=>$rf);
        if(isset($op['join'])){
            self::$_relations[get_called_class()][$fi]['join'] = $op['join'];
        }else{
            self::$_relations[get_called_class()][$fi]['join'] = 'left';
        }
        if(isset($op['talias'])){
            self::$_relations[get_called_class()][$fi]['talias'] = $op['talias'];
        }
    }

    public function hasMany($fi,$rt,$rf,$op=array()){
        $namespace = __NAMESPACE__;
        if(isset($op['namespace'])){
            $namespace = $op['namespace'];
        }
        parent::hasMany($fi, $namespace."\\".$rt, $rf,$op);
    }
    public function hasManyToMany($fields,$intermediateModel,$intermediateFields,$intermediateReferencedFields,$referencedModel,$referencedFields,$op=array()){
        $namespace = __NAMESPACE__;
        if(isset($op['namespace'])){
            $namespace = $op['namespace'];
        }
        parent::hasManyToMany($fields,$namespace."\\".$intermediateModel,$intermediateFields,$intermediateReferencedFields,$namespace."\\".$referencedModel,$referencedFields,$op);
    }
    public function columnMap() {
        return [];
    }

    /**
     * @param array $data
     * @param array $blockProps 禁止传值的属性名数组
     * @param array $appendProps 除model自身的字段属性外，可另追加的属性值
     */
    public function initData($data=array(),$blockProps=array(),$appendProps=array()){
        $columns = $this->columnMap();
        foreach ($data as $key=>$value){
            //根据值来判断
            if((array_search($key,$columns) || in_array($key,$appendProps)) && !in_array($key,$blockProps)){
                $this->{$key} = $value;
            }
        }
    }
    /**
     * 取得主键属性名
     * @return string
     */
    public function getPrimaryField(){
        return 'id';
    }
    /**
     * 根据字段返回固定格式日期
     * @return string
     */
    public function getDataFormat($field){
        return date('Y-m-d H:i:s',$field);
    }
    /**
     * 取得有变化值的字段与值
     * @return array
     */
    public function getChangedFieldAndData(){
        $field = $this->getChangedFields();
        $data  = array();
        if(is_string($field)){
            $data[$field] = $this->{$field};
        }elseif(is_array($field)){
            foreach($field as $f){
                $data[$f] = $this->{$f};
            }
        }
        return $data;
    }

    /**
     * 并联查数据
     * ——当数据量大时可能会影响性能
     * @deprecated
     * @param \Kuga\DTO\ModelCondition $cond
     * @return \Phalcon\Mvc\Model\Resultset\Simple || Array
     */
    public function joinFind($cond,$cols=array()){
        if(!is_object($cond) && preg_match('/^(\d+)$/is',$cond)&& intval($cond)!=0){
            $id = $cond;
            $cond = new ModelCondition();
            $cond->condition = '`'.$this->getPrimaryField().'`=:id';

            $cond->bind['id'] = $id;
            $cond->singleRecord = true;
        }
        if($cond->singleRecord){
            $cond->page = 1;
            $cond->limit= 1;
        }
        $this->setSmartCondition($cond->condition);
        $this->setSmartColumn($cols,$this);
        $this->setSmartLimitPage($cond->page,$cond->limit);
        if($cond->orderBy){
            $this->setSmartOrderBy($cond->orderBy);
        }
        $this->setResultToArray($cond->returnArray);
        $this->setSmartCache($cond->enableCache);
        $this->setGroupBy($cond->groupBy);

        $result = $this->executeSmartFinder($cond->bind,false,$cond->bindType);
        if($result && $cond->singleRecord){
            //return $cond->returnArray?$result[0]:$result->getFirst();
            return $result[0];
        }else{
            return $result;
        }
    }
    /**
     * 按ModelCondition条件统计记录
     * @deprecated
     * @param \Kuga\DTO\ModelCondition $cond
     * @return integer
     */
    public function joinCount($cond){
        $this->initSmartFinder(true);
        $this->setSmartCondition($cond->condition);
        $this->setResultToArray(true);
        $this->setSmartCache($cond->enableCache);
        return intval($this->executeSmartFinder($cond->bind,true,$cond->bindType));
    }

    public function toArray($columns=null)
    {
        $array=[];
        if(empty($columns)){
            $array = parent::toArray();
            $objectVars = get_object_vars($this);
            $diff = array_diff_key($objectVars, $array);
            $manager = $this->getModelsManager();
            foreach($diff as $key => $value) {
                if($manager->isVisibleModelProperty($this, $key)) {
                    $array += [$key => $value];
                }
            }
        }
        if(!empty($columns)){
            foreach($columns as $v){
                if(!array_key_exists($v,$array) && isset($this->{$v})){
                    $array[$v] = $this->{$v};
                }
            }
        }
        return $array;
    }
    public function beforeUpdate(){
        return true;
    }
    public function beforeCreate(){
        return true;
    }
    public function beforeSave(){
        return true;
    }

}