<?php
namespace Kuga\Core\Base;
use Kuga\Model\Exception;
use Phalcon\Mvc\Model\Resultset\Simple;
/**
 * 可以实现例：select rid,rid;name,uid;username from t_role_user a left join t_role b on a.rid=bid left join t_user c on a.uid=c.uid
 * 例.
 *      $model = new RoleUserModel();
$model->setSmartColumn(array('`rid`','`rid;name`'=>'`roleName`','`uid;userName`'=>'uname','`rid;roleType`','`uid;userName`'));
$model->setSmartCondition('rid=?');
$model->setResultToArray(true);
$s = $model->executeSmartFinder(array(4));

说明：
（1）字段用要``引起来，可以用*
（2）字段名要用model的属性字段名，不是数据库字段
（3）当model定义了belongTo时，可以通过belongTo的定义来取得引用Model的属性，例：
1）VipModel的rankId表示等级id，引用自VipRank的id属性
2）如果想要取得VipRank的name属性，可以用rankId;name，表示根据VipModel的rankId关联的VipRank表中取name属性
3）即用【引用id+分号+被引用Model的属性名】可以并联取得被引用Model的属性名
4）暂不支持【引用id+分号+星号】，例不支持这种rid;*

（4）setSmartCondition中需要替换的变量请用?代替，然后按顺序在executeSmartFinder中传入相应值数组，例可以这样：
$model->setSmartCondition('rid=? and rid;name=?');
$model->executeSmartFinder(array(4,'超级用户'));

或
$model->setSmartCondition('rid=:rid and rid;name=:name');
$model->executeSmartFinder(array('rid'=>4,'name'=>'超级用户'));

相当于执行 rid=4 and rid;name="超级用户"
使用的前提条件：
（1）model必须定义好columnMap方法
（2）想要实现【引用id+分号+被引用Model的属性名】效果，必须有定义好belongTo
 * @author dony
 *
 */
trait SmartFinderTrait{
    /**
     * 标识字段名的开始字符
     * @var string
     */
    private $_fieldMarginCodeStart = '`';
    /**
     * 标识出字段名的后面字符
     * @var string
     */
    private $_fieldMarginCodeEnd   = '`';
    /**
     * 别名前辍，设为t后，多个表将用t1,t2这种别名
     * @var string
     */
    private $_tPrefix = 't';
    private $_columns = array();
    /**
     * 临时中间变量用
     * @var array
     */
    private $_current = array();
    /**
     * where语句
     * @var string
     */
    private $_where = '';
    /**
     * select出来的结果是否转为数组，还是用phalcon的ResultSet
     * @var boolean
     */
    private $_resultToArray = false;
    /**
     * 主表相关存储数据
     * @var array
     */
    private $_masterData = array();
    /**
     * 程序中存储offset,limit,where,等过程数据
     * @var array
     */
    private $_smartData;
    private $_parsedColumns = [];

    protected $joinColumnAppendProps = [];
    /**
     * 设置要取的字段,字段是model属性字段，非真实的数据库字段名，字段名必须用``引起来
     *
     * @param array $cols
     */
    public function setSmartColumn($cols = array()){
        $this->initSmartFinder();

        foreach($cols as $origFieldName=>$fieldName){
            if($fieldName=='*'){
                $this->_columns = array_merge($this->_columns,$this->_fetchAllSmartColumns($this->_masterData['alias'], $this));
// 				$mapping = $this->columnMap();
// 				foreach($mapping as $dbField=>$propField){
// 					$this->_columns[]=$this->_masterData['alias'].'.'.$dbField.' as '.$propField;
// 				}

            }elseif(is_numeric($origFieldName)){
                $tmp = $this->_pickOutField($fieldName);
                //list($name,$map) = each($tmp);
                if(isset($this->_smartData['mapping'][$tmp])){
                    $k = $this->_smartData['mapping'][$tmp];
                    $tmp.=' as '.$k;
                    if(!in_array($k,$this->_parsedColumns)){
                        $this->_parsedColumns[] = $k;
                    }
                }
                if(!in_array($tmp, $this->_smartData['blackColumns'])){
                    $this->_columns[] = $tmp;
                }
            }else{
                $tmp = $this->_pickOutField($origFieldName);
                //list($name,$map) = each($tmp);
                if(!in_array($fieldName, $this->_smartData['blackColumns'])){
                    $this->_columns[] = $tmp.' as '.$fieldName;
                }
                if(!in_array($fieldName,$this->_parsedColumns)){
                    $this->_parsedColumns[] = $fieldName;
                }

            }
        }
    }
    /**
     * 不需要取到的字段,不带``符号
     * @param unknown $cols
     */
    public function setSmartBlackColumn($cols=array()){
        $this->_smartData['blackColumns'] = $cols;
    }
    public function setSmartCache($bol){
        $this->_smartData['cache'] = $bol;
    }
    private function _fetchAllSmartColumns($alias,$model){
        $mapping = $model->columnMap();
        $columns = array();
        if(!is_array($this->_smartData['blackColumns'])){
            $this->_smartData['blackColumns'] = [];
        }
        foreach($mapping as $dbField=>$propField){
            if(in_array($propField, $this->_smartData['blackColumns'])){
                continue;
            }
            $columns[]=$alias.'.'.$dbField.' as '.$propField;
            $this->_smartData['mapping'][$alias.'.'.$dbField] = $propField;
            $this->_parsedColumns[] = $propField;
        }
        return $columns;
    }
    /**
     * 结果是返回数组
     * @param boolean $bol
     */
    public function setResultToArray($bol){
        $this->_resultToArray = $bol;
    }
    /**
     * 设置查询条件，条件中的字段是model属性字段，非真实的数据库字段名
     * @param string $strCondition 例uid=? and sid=? or id between ? and ?  问号在executeSmartFinder时传递
     * @return string
     */
    public function setSmartCondition($strCondition=''){
        $this->initSmartFinder();
        $this->_where = $this->_pickOutField($strCondition);
// 		$s = preg_replace_callback('/([a-zA-Z0-9\_\-;]+)([\s]{0,})=([\s]{0,})(.*)/iU',function($m){
// 			$tmp = $this->_analyseRelation($m[1]);
// 			list($name,$map) = each($tmp);
// 			return $name.'='.$m[4];
// 		}, $strCondition);
// 		$this->_where = $s;
    }
    /**
     * 设置翻页
     * @param number $page 页码
     * @param number $limit 每页显示数
     */
    public function setSmartLimitPage($page=1,$limit=10){
        $this->initSmartFinder();
        $this->_smartData['limit']  = intval($limit);
        $page   = intval($page);
        $this->_smartData['offset'] = ($page -1) * $this->_smartData['limit'];
        $this->_smartData['offset'] = $this->_smartData['offset']>0?$this->_smartData['offset']:0;

    }
    /**
     * 设置排序
     * @param string $order `属性字段名1` desc,`属性字段名2` desc,
     */
    public function setSmartOrderBy($orderString=''){
        $this->initSmartFinder();
        if(!empty($orderString)){
            $this->_smartData['order'] = $this->_pickOutField($orderString);
        }
    }
    /**
     * 设置group
     * @param string $group `属性字段名1`,`属性字段名2`
     */
    public function setGroupBy($groupString=''){
        $this->initSmartFinder();
        if(!empty($groupString)){
            $this->_smartData['group'] = $this->_pickOutField($groupString);
        }
    }


    /**
     * 执行智能查询
     * @param array $bind 条件中需要绑定的值
     * @param boolean $count 是否执行count
     * @return \Phalcon\Mvc\Model\Resultset\Simple
     */
    public function executeSmartFinder($bind=array(),$count=false){
        $this->initSmartFinder();
        $columns = $this->_columns;
        $offset  = $this->_smartData['offset'];
        $limit   = $this->_smartData['limit'];

        $cacheEngine = $this->getDI()->getShared('cache');
        $sd = $this->_smartData;
        if(isset($sd['cache'])){
            unset($sd['cache']);
        }
        $cacheId     = array('column'=>$this->_columns,'count'=>$count,'smartData'=>$sd,'returnArray'=>$this->_resultToArray);
        $cacheId['masterData']=$this->_masterData;
        $cacheId['where'] = $this->_where;
        $cacheId['bind'] = $bind;
        $cacheId = md5(serialize($cacheId));
        $data = $cacheEngine->get($cacheId);
        if(isset($this->_smartData['cache']) &&
            $this->_smartData['cache'] && $data){
            return $data;
        }else{

            //统计记录时，不需要用offset,limit以及指定的column
            if($count){
                $columns  = array('count(1) as __smartFinderCount');
                $offset = false;
                $limit  = false;
            }
            if(empty($columns)){
                $mappings = $this->columnMap();
                $this->_parsedColumns = [];

                foreach($mappings as $dbField => $propField){
                    $columns[] = $this->_masterData['alias'].'.'.$dbField.' as '.$propField;
                    $this->_parsedColumns[] = $propField;
                }
            }

            $sql = 'select ';
            $sql.= join(',',$columns).' from '.$this->_masterData['model']->getSource().' as '.$this->_masterData['alias'];

            foreach($this->_masterData['join'] as $item){
                list($alias,$table) = each($item['table']);
                $sql.='  '.$item['direct'].' join '.$table.' as '.$alias.' on '.$item['condition'];
            }
            if($this->_where){
                $sql.=' where '.$this->_where;
            }
            if(!empty($this->_smartData['group'])){
                $sql.=' group by '.$this->_smartData['group'];
            }
            if(!empty($this->_smartData['order'])){
                $sql.=' order by '.$this->_smartData['order'];
            }
            if($offset!==false && $limit){
                $sql.= ' limit '.$offset.','.$limit;
            }
            $result =  $this->getReadConnection()->query($sql,$bind);
            if($result->numRows()){
                $rows = new Simple(null,$this,$result);
                if(!$count || $this->_smartData['group']){
                    //调用filter会触发afterFetch的执行
                    if(method_exists($this, 'afterFetch')){
                        $c = [];
                        $rows2 = $rows->filter(function($r) use(&$c){
//                            //用了afterFetch时，可能对要取回的数据列会产生影响，比如可能增加了一些列，这些列在$this->_parsedColumns中又没有，
//                            //这样在toArray($this->_parsedColumns)时afterFetch追加的一些列数据可能就会丢失掉了。
//                            if(empty($c) && $r->joinColumnAppendProps){
//                                $c = $r->joinColumnAppendProps;
//                            }
                            return $r;
                        });

                        if($this->_resultToArray) {
                            //2017.7.14
                            //原意是用joinColumnAppendProps+_parsedColumns，以防在afterFetch之后，afterFetch追加的字段不会
                            //被toArray出来，现在要求是在afterFetch中如果加了字段，必须在toArray方法中要做转化
                            //所以joinColumnAppendProps方法暂时没用了
                            //$c = array_merge($c,$this->_parsedColumns);

                            if (is_array($rows2) && sizeof($rows2) > 0) {
                                foreach ($rows2 as &$i) {
                                    $i = $i->toArray($this->_parsedColumns);
                                }
                            }
                            $rows = $rows2;
                        }
                    }else{
                        if($this->_resultToArray){
                            //$rows->setHydrateMode(Resultset::HYDRATE_ARRAYS);
                            $rows = $rows->toArray($this->_parsedColumns);
                        }

                    }
                    $data= $rows;
                }else{
                    $rows = $rows->toArray();
                    if(!$rows){
                        $data= 0;
                    }else{
                        //$tmp = $rows->toArray();
                        $tmp = $rows;
                        $data= $tmp[0]['__smartFinderCount'];
                    }
                }
                if(isset($this->_smartData['cache']) && $this->_smartData['cache']){
                    $result= $cacheEngine->set($cacheId,$data);
                }
            }else{
                $data = $count?0:null;
            }
            return $data;
        }
    }
    /**
     * 根据字段名结合model，分析出需要join的表以及该字段对应的实际数据库字段
     * @param string $fieldName 属性字段名，如username或带有分号的rid;name
     */
    private function _analyseRelation($fieldName){
        $this->_current['relations']  = $this->_masterData['relations'];
        $this->_current['model']      = $this->_masterData['model'];
        //$fieldName = $this->_pickOutField($fieldName);
        if(stripos($fieldName, $this->_masterData['fsw'])!==false){
            $fieldInfo = explode($this->_masterData['fsw'],$fieldName);
            $name      = array_pop($fieldInfo);
            $fakeField = '';
            //按;切分后，第一个的要加的别名肯定是主表的别名
            $prevTableAlias = $this->_masterData['alias'];

            foreach($fieldInfo as $item){
                $item = trim($item);
                $prefix = '';
                if(stripos($item,'.')!==false){
                    list($prefix,$item2)=explode('.',$item);
                    $item = $item2;
                }
                if(!array_key_exists($item, $this->_current['relations'])){
                    throw new Exception($item.'不在model【'.get_class($this->_current['model']).'】的relation定义中');
                }else{
                    $ref = new \ReflectionClass($this->_current['relations'][$item]['model']);
                    $nextModel = $ref->newInstance();
                    if(!array_key_exists($nextModel->getSource(), $this->_masterData['join'])){
                        $this->_current['tableIndex']++;
                        $itemFields      = $this->_mapDbField($this->_current['model'],$item);
                        $relationFields  = $this->_mapDbField($nextModel, $this->_current['relations'][$item]['id']);
                        $talias          = isset($this->_current['relations'][$item]['talias'])?$this->_current['relations'][$item]['talias']:$this->_tPrefix.$this->_current['tableIndex'];
                        $join['table']               = array($talias=>$nextModel->getSource());
                        $join['index']               = $this->_current['tableIndex'];
                        $join['condition']           = $prevTableAlias.'.'.$itemFields['dbField'].'='. $talias.'.'.$relationFields['dbField'];
                        $join['direct']              = $this->_current['relations'][$item]['join'];
                        $this->_masterData['join'][$nextModel->getSource()] = $join;
                        $prevTableAlias = $talias;
                    }else{
                        if(isset($this->_current['relations'][$item]['talias'])){
                            $talias = $this->_current['relations'][$item]['talias'];
                            $join['table']               = array($talias=>$nextModel->getSource());
                            //$join['index']               = $this->_current['tableIndex'];
                            $this->_current['tableIndex']++;
                            $itemFields      = $this->_mapDbField($this->_current['model'],$item);
                            $relationFields  = $this->_mapDbField($nextModel, $this->_current['relations'][$item]['id']);
                            $join['condition']           = $prevTableAlias.'.'.$itemFields['dbField'].'='. $talias.'.'.$relationFields['dbField'];
                            $join['direct']              = $this->_current['relations'][$item]['join'];
                            $this->_masterData['join'][$nextModel->getSource().$this->_current['tableIndex']] = $join;
                            $prevTableAlias = $talias;
                        }else{
                            //Fixed:不能用之前index值来重置_current['tableIndex']，因为随层级增加_current['tableIndex']已变化了
                            //$this->_current['tableIndex'] = $this->_masterData['join'][$nextModel->getSource()]['index'];

                            $s = array_keys($this->_masterData['join'][$nextModel->getSource()]['table']);
                            $prevTableAlias = $s[0];
                        }
                    }
                    $this->_current['relations'] = $nextModel->getRelations();
                    $this->_current['model']     = $nextModel;
                }
            }

            $talias = '';
            if(stripos($name, '.')!==false){
                $s = explode('.',$name);
                $name = $s[1];
                $talias = $s[0];
            }
            $joinInfo = $this->_locateJoin($talias,$this->_current['model']->getSource());
            //别名要从_masterData['join']的信息中取
            //$joinInfo = $this->_masterData['join'][$this->_current['model']->getSource()];

            list($alias,$table) = each($joinInfo['table']);
            $fields = $this->_mapDbField($this->_current['model'], $name);
            $name   = $alias.'.'.$fields['dbField'];
            $mapName = $fields['propField'];
            $this->_smartData['mapping'][$name] = $mapName;
        }else{
            $fields = $this->_mapDbField($this->_masterData['model'], $fieldName,false);
            $name   = $this->_masterData['alias'].'.'.$fields['dbField'];
            $mapName = $fields['propField'];
            $this->_smartData['mapping'][$name] = $mapName;
        }
        return $name;
    }

    private function _locateJoin($alias,$source=''){
        if($alias){
            foreach($this->_masterData['join'] as $sourceKey=>$join){
                if(isset($join['table'][$alias])){
                    return $join;
                }
            }
        }
        return $this->_masterData['join'][$source];
    }
    /**
     * 从`和`之间分离出字段名，然后进行换算转义
     * @param string $str
     * @return mixed|multitype:
     */
    private function _pickOutField($str){
        return preg_replace_callback('/'.$this->_fieldMarginCodeStart.'([a-zA-Z0-9\_\-;.]+)'.$this->_fieldMarginCodeEnd.'/iU',function($m){
            $tmp = $this->_analyseRelation($m[1]);
            //list($name,$map) = each($tmp);
            //$this->_smartData['mapping'][$name] = $map;
            return $tmp;
        }, $str);
    }

    /**
     * 计算出实际字段名与属性字段名
     * @param string|ModelBase $model
     * @param string $field
     * @param boolean $strict 强制字段名必须存在
     * @throws Exception
     * @return array
     */
    private function _mapDbField($model,$field,$strict=true){
        if(is_string($model)){
            $ref = new \ReflectionClass($model);
            $model = $ref->newInstance();
        }
        $mappings = $model->columnMap();
        $keys = array_keys($mappings,$field);
        if(empty($keys)){
            if($strict)
                throw new Exception($field.'字段不在'.get_class($model).'的columnMap中');
            else
                $keys[0] = $field;
        }
        return array('dbField'=>$keys[0],'propField'=>$field);
    }
    /**
     * 初始化
     * @param boolean $reset 当有初始化过时是否重置
     */
    public function initSmartFinder($reset=false){
        if(!isset($this->_smartData['inited'])||!$this->_smartData['inited']||$reset){
            //字段分隔符
            $index = 1;
            $this->_masterData['fsw'] =  ';';
            $this->_masterData['relations'] = $this->getRelations();
            $this->_masterData['model']     = $this;
            $this->_masterData['join']      = array();
            $this->_masterData['alias']     = $this->_tPrefix.$index;

            $this->_current['tableIndex'] = $index;
            $this->_current['relations']  = $this->_masterData['relations'];
            $this->_current['model']      = $this;

            $this->_smartData['offset'] = 0;
            $this->_smartData['limit']  = 10;
            $this->_smartData['order']  = array();
            $this->_smartData['group']  = array();
            $this->_columns = array();
            $this->_smartData['cache'] = false;
        }
        $this->_smartData['inited'] = true;
        if(!isset($this->_smartData['blackColumns']) ||!is_array($this->_smartData['blackColumns'])){
            $this->_smartData['blackColumns'] = [];
        }
    }

}