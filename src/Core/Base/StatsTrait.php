<?php
/**
 * 分享、查看、点赞的处理
 * 目前是将这些数据分享出来，在专门的存储系统上处理，例如Redis
 * @author Donny
 * @copyright
 */
namespace Kuga\Core\Base;
trait  StatsTrait{

    private function initStorage(){
        $this->storage = $this->getDI()->getShared('simpleStorage');
    }
    /**
     * @var \Qing\Lib\SimpleStorage
     */
    protected $storage;
    /**
     * 数据统计Key
     * 有些业务需要保存点击量、点赞数、分享数等的，用不同的statsKey来区分这些数据
     * @var string
     */
    protected $statsKey = 'misc';

    /**
     * 数据添加到集合中
     * @param $prefixKey
     * @param $value
     */
    protected function _addToSet($prefixKey,$value){
        $id = $this->{$this->getPrimaryField()};
        return $this->storage->addToSet($prefixKey.$id,$value);
    }

    /**
     * 从集合国移除数据
     * @param $prefixKey
     * @param $value
     */
    protected function _deleteFromSet($prefixKey,$value){
        $id = $this->{$this->getPrimaryField()};
        return $this->storage->deleteFromSet($prefixKey.$id,$value);
    }

    /**
     * 是否在集合中
     * @param $prefixKey
     * @param $value
     * @return mixed
     */
    protected function _isInSet($prefixKey,$value){
        $id = $this->{$this->getPrimaryField()};
        return $this->storage->isInSet($prefixKey.$id,$value);
    }

    /**
     * 集合统计
     * @param $prefixKey
     * @return mixed
     */
    protected function _countSet($prefixKey){
        $id = $this->{$this->getPrimaryField()};
        return $this->storage->countSet($prefixKey.$id);
    }
    /**
     * 统计指标计数增加
     * @param $prefixKey
     * @return mixed
     */
    protected function _addStats($prefixKey,$amount=1){
        if($amount>=99999999||$amount<=-99999999) {
            throw new \Exception($this->_('统计计数范围只能在 -99,999,999 和 99,999,999之间'));
        }
        $id = $this->{$this->getPrimaryField()};
        return $this->storage->incrementBy($prefixKey.$id,$amount);
    }

    /**
     * 取得统计指标
     * @param $prefixKey
     * @return int
     */
    protected function _getStats($prefixKey){
        $id = $this->{$this->getPrimaryField()};
        $s = $this->storage->get($prefixKey.$id);
        return intval($s);
    }

    /**
     * 清掉所有点赞数据
     */
    public function clearLikedData(){
        $id = $this->{$this->getPrimaryField()};
        $this->storage->delete($this->statsKey.':likedSet:'.$id);
        $this->storage->delete($this->statsKey.':cntLiked:'.$id);
    }
    /**
     * 统计点赞次数
     * @$onlyRealData boolean 是否只返回真实数据，默认为false
     * @return mixed
     */
    public  function cntLiked($onlyRealData = false){
        $realData = $this->_countSet($this->statsKey.':likedSet:');
        $fakeData = $this->_getStats($this->statsKey.':cntLiked:');
        return $onlyRealData?$realData:($realData + $fakeData);
    }

    /**
     * 增加或减少假的点赞数据
     * @param int $delta
     */
    public function addFakeLiked($delta=1){
        $this->_addStats($this->statsKey.':cntLiked:',$delta);
    }


    /**
     * 增加点赞
     * @return mixed
     */
    public function addLiked($value){
        $result =  (boolean)$this->_addToSet($this->statsKey.':likedSet:',$value);
        return $result;
    }

    /**
     * 移除点赞
     * @param $value
     */
    public function delLiked($value){
        return (boolean)$this->_deleteFromSet($this->statsKey.':likedSet:',$value);
    }

    /**
     * 清掉所有浏览数据
     */
    public function clearVisitedData(){
        $id = $this->{$this->getPrimaryField()};
        $this->storage->delete($this->statsKey.':cntVisited:'.$id);
        $this->storage->delete($this->statsKey.':cntRealVisited:'.$id);
    }

    /**
     * 清掉所有分享统计数据
     */
    public function clearSharedData(){
        $id = $this->{$this->getPrimaryField()};
        $this->storage->delete($this->statsKey.':cntShared:'.$id);
        $this->storage->delete($this->statsKey.':cntRealShared:'.$id);
    }

    /**
     * 是否点赞过
     * @param $value
     * @return bool
     */
    public function isLiked($value){
        return (boolean)$this->_isInSet($this->statsKey.':likedSet:',$value);
    }

    /**
     * 统计分享次数
     * @param $onlyRealData boolean 是否只返回真实数据，默认为false
     * @return mixed
     */
    public  function cntShared($onlyRealData = false){
        $cntFakeShared =  $this->_getStats($this->statsKey.':cntShared:');
        $cntRealShared =  $this->_getStats($this->statsKey.':cntRealShared:');
        return $onlyRealData?$cntRealShared:($cntFakeShared + $cntRealShared);
    }

    /**
     * 增加分享次数
     * @param $amount
     * @param $isFaked
     * @return mixed
     */
    public function addShare($amount=1,$isFaked=false){
        if($isFaked) {
            $cntFakeShared =  $this->_addStats($this->statsKey . ':cntShared:', $amount);
            $cntRealShared = $this->_getStats($this->statsKey.':cntRealShared:');
            return $cntRealShared + $cntFakeShared;
        }else{
            $cntFakeShared = $this->_getStats($this->statsKey.':cntShared:');
            $cntRealShared = $this->_addStats($this->statsKey.':cntRealShared:',$amount);
            return $cntRealShared + $cntFakeShared;
        }
    }

    /**
     * 统计查看次数
     * @param $onlyRealData boolean 是否只返回真实数据，默认为false
     * @return mixed
     */
    public  function cntVisited($onlyRealData=false){
        //return $this->_getStats($this->statsKey.':cntVisited:');
        $cntFake =  $this->_getStats($this->statsKey.':cntVisited:');
        $cntReal =  $this->_getStats($this->statsKey.':cntRealVisited:');
        //return $cntFake + $cntReal;
        return $onlyRealData?$cntReal:($cntFake + $cntReal);
    }

    /**
     * 增加查看次数
     * @param $amount
     * @param $isFaked
     * @return mixed
     */
    public function addVisited($amount=1,$isFaked=false){
        if($isFaked) {
            $cntFake =  $this->_addStats($this->statsKey . ':cntVisited:', $amount);
            $cntReal = $this->_getStats($this->statsKey.':cntRealVisited:');
            return $cntFake + $cntReal;
        }else{
            $cntFake = $this->_getStats($this->statsKey.':cntVisited:');
            $cntReal = $this->_addStats($this->statsKey.':cntRealVisited:',$amount);
            return $cntFake + $cntReal;
        }
        //return $this->_addStats($this->statsKey.':cntVisited:',$amount);
    }

    /**
     *
     * @param $ids
     * @param array $keys
     * @param bool $onlyRealData boolean 是否只返回真实数据，默认为false
     * @return array
     */
    public  function getAllStatsByIds($ids,$keys=[],$onlyRealData=false){
        if(empty($keys)){
            $keys = ['cntLiked','cntVisited','cntShared'];
        }
        $fetchKeys = $keys;
        if(in_array('cntVisited',$keys)) {
            $fetchKeys[] = 'cntRealVisited';
        }
        if(in_array('cntShared',$keys)) {
            $fetchKeys[] = 'cntRealShared';
        }
        $mapping = [];
        $this->storage->begin();
        $index=0;
        foreach($ids as $id){
            foreach($fetchKeys as $k){
                $mapping[$k.$id] = $index;
                $this->storage->get($this->statsKey.':'.$k.':'.$id);
                $index++;
            }
        }
        if(in_array('cntLiked',$fetchKeys)) {
            foreach ($ids as $id) {
                $mapping['likedSet'.$id] = $index;
                $this->storage->countSet($this->statsKey . ':likedSet:' . $id);
                $index++;
            }
        }
        $result = $this->storage->commit();
        $data   = [];
        $i = 0;
        foreach($ids as $id) {
            foreach ($fetchKeys as  $k) {
                $index = $mapping[$k.$id];
                $data[$id][$k] = intval($result[$index]);
                $i++;
            }
            if($onlyRealData){
                if(in_array('cntVisited',$fetchKeys) && in_array('cntRealVisited',$fetchKeys)){
                    $data[$id]['cntVisited'] = $data[$id]['cntRealVisited'];
                    //unset($data[$id]['cntRealVisited']);
                }
                if(in_array('cntShared',$fetchKeys) && in_array('cntRealShared',$fetchKeys)){
                    $data[$id]['cntShared'] = $data[$id]['cntRealShared'];
                    //unset($data[$id]['cntRealShared']);
                }
            }else{
                if(in_array('cntVisited',$fetchKeys)){
                    $data[$id]['cntVisited']+= $data[$id]['cntRealVisited'];
                    //unset($data[$id]['cntRealVisited']);
                }
                if(in_array('cntShared',$fetchKeys)){
                    $data[$id]['cntShared']+= $data[$id]['cntRealShared'];
                    //unset($data[$id]['cntRealShared']);
                }
            }
        }
        if(in_array('cntLiked',$keys)){
            foreach($ids as $id) {
                $index = $mapping['likedSet'.$id];
                if($onlyRealData)
                    $data[$id]['cntLiked'] = $result[$index];
                else
                    $data[$id]['cntLiked'] += $result[$index];
                $i++;
            }
        }
        return $data;
    }
    /**
     * 一次性取得所有统计内容
     * @param array $keys
     * @return array
     */
    public function getAllStats($keys=[],$onlyRealData=false){
        if(empty($keys)){
            $keys = ['cntLiked','cntVisited','cntShared'];
        }
        $fetchKeys = $keys;
        if(in_array('cntVisited',$keys)) {
            $fetchKeys[] = 'cntRealVisited';
        }
        if(in_array('cntShared',$keys)) {
            $fetchKeys[] = 'cntRealShared';
        }
        $mapping = [];
        $index = 0;
        $this->storage->begin();
        foreach($fetchKeys as $k){
            $mapping[$k] = $index;
            $f=$this->_getStats($this->statsKey.':'.$k.':');
            $index++;
        }


        if(in_array('cntLiked',$keys)){
            $mapping['likedSet'] = $index;
            $this->_countSet($this->statsKey.':likedSet:');
        }
        $result = $this->storage->commit();
        $data   = [];
        foreach($fetchKeys as $i=>$k){
            //$data[$k] = intval($result[$i]);
            $index = $mapping[$k];
            $data[$k] = intval($result[$index]);
        }
        if($onlyRealData){
            if(in_array('cntVisited',$fetchKeys) && in_array('cntRealVisited',$fetchKeys)){
                $data['cntVisited'] = $data['cntRealVisited'];
                //unset($data['cntRealVisited']);
            }
            if(in_array('cntShared',$fetchKeys) && in_array('cntRealShared',$fetchKeys)){
                $data['cntShared'] = $data['cntRealShared'];
                //unset($data['cntRealShared']);
            }
        }else{
            if(in_array('cntVisited',$fetchKeys)){
                $data['cntVisited']+= $data['cntRealVisited'];
                //unset($data['cntRealVisited']);
            }
            if(in_array('cntShared',$fetchKeys)){
                $data['cntShared']+= $data['cntRealShared'];
                //unset($data['cntRealShared']);
            }
        }

        if(in_array('cntLiked',$keys)){
            $index = $mapping['likedSet'];
            if($onlyRealData)
                $data['cntLiked'] = $result[$index];
            else
                $data['cntLiked'] += $result[$index];
            //$data['cntLiked']+=$result[sizeof($result)-1];
        }

        return $data;
    }
}