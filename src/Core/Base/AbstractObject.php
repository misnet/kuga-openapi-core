<?php 
namespace Kuga\Core\Base;
abstract class AbstractObject{
	public function initData($data=array()){
		$ref = new \ReflectionObject($this);
		foreach ($data as $key=>$value){
			if($ref->hasProperty($key)){
				$this->{$key} = $value;
			}
		}
	}
}