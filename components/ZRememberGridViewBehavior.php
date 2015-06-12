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
	 * Default page number
	 * 
	 * @var string
	 */
	public $page=null;
	/**
	 * Default sort variable
	 * 
	 * @var string
	 */
	public $sort=null;
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
		

	private function readGridviewParams()
	{
	    $modelName = get_class($this->owner);
	    $attributes = $this->owner->getSafeAttributeNames();

	    // set any default value

	    if (is_array($this->defaults) && (null===Yii::app()->user->getState($modelName . __CLASS__. 'defaultsSet', null))) {
	        foreach ($this->defaults as $attribute => $value) {
	            if (null === (Yii::app()->user->getState($this->getStatePrefix() . $attribute, null))) {
	                Yii::app()->user->setState($this->getStatePrefix() . $attribute, $value);
	            }
	        }
	        Yii::app()->user->setState($modelName . __CLASS__. 'defaultsSet', 1);
	    }

		if (!empty($this->page) && (null===Yii::app()->user->getState($modelName . '_page', 1))) {
			Yii::app()->user->setState($this->getStatePrefix() . '_page', $this->page);
		}

		if (!empty($this->sort) && (null===Yii::app()->user->getState($modelName . '_sort'))) {
			Yii::app()->user->setState($this->getStatePrefix() . '_sort', $this->sort);
		}
	    
	    // set values from session

	    foreach ($attributes as $attribute) {
	        if (null != ($value = Yii::app()->user->getState($this->getStatePrefix() . $attribute, null))) {
	            try {
	                $this->owner->$attribute = $value;
	            } catch (CException $e) {}
	        }
	    }
	}

	private function saveGridviewParams()
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
		if (!$this->owner->scenario == 'search' && $this->owner->scenario != $this->rememberScenario) return;

		$this->owner->unsetAttributes();
		$className = get_class($this->owner);

		// store also sorting order
		$key = $className.'_sort';
		if (!empty($_GET[$key])) {
			Yii::app()->user->setState($this->getStatePrefix() . '_sort', $_GET[$key]);
		} else {
			$val = Yii::app()->user->getState($this->getStatePrefix() . '_sort');
			if(!empty($val)) $_GET[$key] = $val;
		}

		// store active page in page
		$key = $className.'_page';
		if (!empty($_GET[$key])) {
			Yii::app()->user->setState($this->getStatePrefix() . '_page', $_GET[$key]);
		} elseif (!empty($_GET["ajax"])){
			// page 1 passes no page number, just an ajax flag
			Yii::app()->user->setState($this->getStatePrefix() . '_page', 1);
		} else {
			$val = Yii::app()->user->getState($this->getStatePrefix() . '_page');
			if (!empty($val)) $_GET[$key] = $val;
		}

		if (isset($_GET[$className])) {
			$this->owner->attributes = $_GET[$className];
			$this->saveGridviewParams();
		} else {
			$this->readGridviewParams();
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
	public function unsetParams()
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

	    Yii::app()->user->setState($this->getStatePrefix() . '_page', null);
	    Yii::app()->user->setState($this->getStatePrefix() . '_sort', null);

	    return $this->owner;
	}
}