<?php
namespace  Kuga\Core\Base;
class ModelConditionObject{
    public $condition='';
    public $bind = array();
    public $limit = 0;
    public $page = 1;
    public $singleRecord = false;
    public $returnArray  = true;
    public $enableCache  = false;
    public $orderBy = '';
    public $groupBy = '';
    public $bindType = [];
}