<?php

abstract class Default_Model_Db_Abstract
{
    /**
     * @var array
     */
    protected static $_instances = array();

    /**
     * Retrieve singleton instance
     *
     * @return Default_Model_Db_Abstract
     */
    protected static function _getInstance($class)
    {
        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class;
        }

        return self::$_instances[$class];
    }

	/**
     * Reset the singleton instance
     *
     * @return void
     */
    protected static function _resetInstance($class)
    {
        self::$_instances[$class] = null;
    }

    protected $_dbTable;

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * Get the instance of Zend_Db_Table to work with
     *
     * @return Zend_Db_Table_Abstract
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            throw new Exception('Missing table data gateway');
        }
        return $this->_dbTable;
    }

    public function getIdFieldName()
    {
        $primary = $this->getDbTable()->info(Zend_Db_Table_Abstract::PRIMARY);
        return $primary;
    }

    /**
     *
     * @param $withFromPart
     * @return Zend_Db_Table_Select
     */
    public function select($withFromPart=false)
    {
        return $this->getDbTable()->select($withFromPart);
    }

    /**
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function find()
    {
        $args = func_get_args();
        return call_user_func_array(array($this->getDbTable(), 'find'), $args);
    }

    /**
     *
     * @return Zend_Db_Table_Row_Abstract
     */
    public function fetchRow()
    {
        $args = func_get_args();
        return call_user_func_array(array($this->getDbTable(), 'fetchRow'), $args);
    }

    /**
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function fetchAll()
    {
        $args = func_get_args();
        return call_user_func_array(array($this->getDbTable(), 'fetchAll'), $args);
    }

    public function beginTransaction()
    {
        $this->getDbTable()->getAdapter()->beginTransaction();
        return $this;
    }

    public function commit()
    {
        $this->getDbTable()->getAdapter()->commit();
        return $this;
    }

    public function rollBack()
    {
        $this->getDbTable()->getAdapter()->rollBack();
        return $this;
    }
}