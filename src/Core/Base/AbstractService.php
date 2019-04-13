<?php
namespace Kuga\Core\Base;
abstract class AbstractService{
    /**
     * @var \Phalcon\DiInterface
     */
	protected $_di;
	/**
     * 
     * @var \Qing\Lib\Translator\Gettext
     */
    protected $translator;
	public function __construct($di=null){
		if(is_null($di)){
			$this->_di = \Phalcon\DI::getDefault();
		}else{
			$this->_di = $di;
		}
		$this->_eventsManager = $this->_di->get('eventsManager');
		$this->translator    = $this->_di->getShared('translator');
	}
	/**
	 * 
	 * @var \Phalcon\Events\Manager
	 */
	protected $_eventsManager;
}