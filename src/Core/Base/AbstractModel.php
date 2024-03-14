<?php
/**
 * 基础Model
 *
 */
namespace Kuga\Core\Base;
use Phalcon\Mvc\Model\Relation;

abstract class AbstractModel extends \Phalcon\Mvc\Model{

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
    public function hasOne($fi,$rt,$rf,$op=array()):Relation{
        $namespace = __NAMESPACE__;
        if(isset($op['namespace'])){
            $namespace = $op['namespace'];
        }
        self::$_relations[get_called_class()][$fi]=array('model'=>$namespace."\\".$rt,'id'=>$rf);
        if(isset($op['join'])){
            self::$_relations[get_called_class()][$fi]['join'] = $op['join'];
        }else{
            self::$_relations[get_called_class()][$fi]['join'] = 'left';
        }
        if(isset($op['talias'])){
            self::$_relations[get_called_class()][$fi]['talias'] = $op['talias'];
        }
        return parent::hasOne($fi, $namespace."\\".$rt, $rf,$op);
    }

    public function belongsTo($fi,$rt,$rf,$op=array()):Relation {
        $namespace = __NAMESPACE__;
        if(isset($op['namespace'])){
            $namespace = $op['namespace'];
        }
        self::$_relations[get_called_class()][$fi]=array('model'=>$namespace."\\".$rt,'id'=>$rf);
        if(isset($op['join'])){
            self::$_relations[get_called_class()][$fi]['join'] = $op['join'];
        }else{
            self::$_relations[get_called_class()][$fi]['join'] = 'left';
        }
        if(isset($op['talias'])){
            self::$_relations[get_called_class()][$fi]['talias'] = $op['talias'];
        }
        return parent::belongsTo($fi, $namespace."\\".$rt, $rf,$op);
    }

    public function hasMany($fi,$rt,$rf,$op=array()) :Relation{
        $namespace = __NAMESPACE__;
        if(isset($op['namespace'])){
            $namespace = $op['namespace'];
        }
        return parent::hasMany($fi, $namespace."\\".$rt, $rf,$op);
    }
    public function hasManyToMany($fields,$intermediateModel,$intermediateFields,$intermediateReferencedFields,$referencedModel,$referencedFields,$op=array()):Relation{
        $namespace = __NAMESPACE__;
        if(isset($op['namespace'])){
            $namespace = $op['namespace'];
        }
        return parent::hasManyToMany($fields,$namespace."\\".$intermediateModel,$intermediateFields,$intermediateReferencedFields,$namespace."\\".$referencedModel,$referencedFields,$op);
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

//    public function toArray($columns=null):array
//    {
//        $array=[];
//        if(empty($columns)){
//            $array = parent::toArray();
//            $objectVars = get_object_vars($this);
//            $diff = array_diff_key($objectVars, $array);
//            $manager = $this->getModelsManager();
//            foreach($diff as $key => $value) {
//                if($manager->isVisibleModelProperty($this, $key)) {
//                    $array += [$key => $value];
//                }
//            }
//        }
//        if(!empty($columns)){
//            foreach($columns as $v){
//                if(!array_key_exists($v,$array) && isset($this->{$v})){
//                    $array[$v] = $this->{$v};
//                }
//            }
//        }
//        return $array;
//    }
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