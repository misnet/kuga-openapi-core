<?php
/**
 * API Parameter Object
 */
namespace Kuga\Core\Api;
class Parameter implements \ArrayAccess{
    private $_data;
    public function __construct($data){
        $this->_data = $data;
    }
    public function toArray(){
        return $this->_data;
    }
    public function isEmpty(){
        return sizeof($this->_data)==0;
    }
    /**
     * Defined by ArrayAccess interface
     * Set a value given it's key e.g. $A['title'] = 'foo';
     * @param mixed key (string or integer)
     * @param mixed value
     * @return void
     */
    public function offsetSet($key, $value) {
//         if ( array_key_exists($key,$this->_data) ) {
            
//         }
        $this->_data[$key] = $value;
    }
    
    /**
     * Defined by ArrayAccess interface
     * Return a value given it's key e.g. echo $A['title'];
     * @param mixed key (string or integer)
     * @return mixed value
     */
    public function offsetGet($key) {
        if ( array_key_exists($key,$this->_data) ) {
            return $this->_data[$key];
        }else{
            return null;
        }
    }
    
    /**
     * Defined by ArrayAccess interface
     * Unset a value by it's key e.g. unset($A['title']);
     * @param mixed key (string or integer)
     * @return void
     */
    public function offsetUnset($key) {
        if ( array_key_exists($key,$this->_data) ) {
            unset($this->_data[$key]);
        }
    }
    
    /**
     * Defined by ArrayAccess interface
     * Check value exists, given it's key e.g. isset($A['title'])
     * @param mixed key (string or integer)
     * @return boolean
     */
    public function offsetExists($offset) {
        return array_key_exists($offset,$this->_data);
    }
}