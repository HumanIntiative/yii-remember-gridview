<?php
/**
*
* ZRememberGridViewBehavior class file.
*
* @author Zein Miftah
* @link http://www.yiiframework.com/
* @version 1.0.0
*
* Copyright (c) 2015, zmiftah
* All rights reserved.
*
* public function behaviors() {
*        return array(
*            'ERememberFiltersBehavior' => array(
*                'class' => 'application.components.ERememberFiltersBehavior',
*                'defaults'=>array(),
*                'defaultStickOnClear'=>false 
*            ),
*        );
* }
*
* To set a scenario add the set call after the instantiation
* Fragment from actionAdmin():
*
* $model=new Persons('search');
* $model->setRememberScenario('scene1');
*/

class ZRememberGridViewBehavior extends CActiveRecordBehavior
{
	/**
	 * Array that holds any default filter value like array('active'=>'1')
	 *
	 * @var array
	 */
	public $defaults=array();
	/**
	 * When this flag is true, the default values will be used also when the user clears the filters
	 *
	 * @var boolean
	 */
	public $defaultStickOnClear=false;
	/**
	* Holds a custom stateId key 
	*
	* @var string
	*/
	private $_rememberScenario=null;


	private function getStatePrefix()
	{
	    $modelName = get_class($this->owner);
	    if ($this->_rememberScenario!=null) {
	        return $modelName.$this->_rememberScenario;
	    } else {
	        return $modelName;
	    }
	}

	public function setRememberScenario($value)
	{
	    $this->_rememberScenario=$value;
	    $this->doReadSave();
	    return $this->owner;
	}

	public function getRememberScenario()
	{
	    return $this->_rememberScenario;
	}
		

	private function readSearchValues()
	{
	    $modelName = get_class($this->owner);
	    $attributes = $this->owner->getSafeAttributeNames();

	    // set any default value

	    if (is_array($this->defaults) && (null==Yii::app()->user->getState($modelName . __CLASS__. 'defaultsSet', null))) {
	        foreach ($this->defaults as $attribute => $value) {
	            if (null == (Yii::app()->user->getState($this->getStatePrefix() . $attribute, null))) {
	                Yii::app()->user->setState($this->getStatePrefix() . $attribute, $value);
	            }
	        }
	        Yii::app()->user->setState($modelName . __CLASS__. 'defaultsSet', 1);
	    }
	    
	    // set values from session

	    foreach ($attributes as $attribute) {
	        if (null != ($value = Yii::app()->user->getState($this->getStatePrefix() . $attribute, null))) {
	            try
	            {
	                $this->owner->$attribute = $value;
	            }
	            catch (Exception $e) {
	            }
	        }
	    }
	}

	private function saveSearchValues()
	{
	    $attributes = $this->owner->getSafeAttributeNames();
	    foreach ($attributes as $attribute) {
	        if (isset($this->owner->$attribute)) {
	            Yii::app()->user->setState($this->getStatePrefix() . $attribute, $this->owner->$attribute);
	        } else {
	            Yii::app()->user->setState($this->getStatePrefix() . $attribute, 1, 1);
	        }
	    }
	}


	private function doReadSave()
	{
	  if ($this->owner->scenario == 'search' || $this->owner->scenario == $this->rememberScenario ) {
	    $this->owner->unsetAttributes();

	    // store also sorting order
	    $key = get_class($this->owner).'_sort';
	    if(!empty($_GET[$key])){
	      Yii::app()->user->setState($this->getStatePrefix() . 'sort', $_GET[$key]);
	    }else {
	      $val = Yii::app()->user->getState($this->getStatePrefix() . 'sort');
	      if(!empty($val))
	        $_GET[$key] = $val;
	    }

	    // store active page in page
	    $key = get_class($this->owner).'_page';
	    if(!empty($_GET[$key])){
	      Yii::app()->user->setState($this->getStatePrefix() . 'page', $_GET[$key]);
	    }elseif (!empty($_GET["ajax"])){
	      // page 1 passes no page number, just an ajax flag
	      Yii::app()->user->setState($this->getStatePrefix() . 'page', 1);
	    }else{
	      $val = Yii::app()->user->getState($this->getStatePrefix() . 'page');
	      if(!empty($val))
	        $_GET[$key] = $val;
	    }

	    if (isset($_GET[get_class($this->owner)])) {
	      $this->owner->attributes = $_GET[get_class($this->owner)];
	      $this->saveSearchValues();
	    } else {
	      $this->readSearchValues();
	    }
	  }
	}


	public function afterConstruct($event)
	{
	    $this->doReadSave();
	}

	/**
	 * Method is called when we need to unset the filters
	 *
	 * @return owner
	 */
	public function unsetFilters()
	{
	    $modelName = get_class($this->owner);
	    $attributes = $this->owner->getSafeAttributeNames();

	    foreach ($attributes as $attribute) {
	        if (null != ($value = Yii::app()->user->getState($this->getStatePrefix() . $attribute, null))) {
	            Yii::app()->user->setState($this->getStatePrefix() . $attribute, 1, 1);
	        }
	    }
	    if ($this->defaultStickOnClear) {
	        Yii::app()->user->setState($modelName . __CLASS__. 'defaultsSet', 1, 1);
	    }
	    return $this->owner;
	}
}