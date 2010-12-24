<?php

class Default_Model_Table_Abstract extends Zend_Db_Table_Abstract
{
    protected static $_tableCache;
    protected $_installSql = '';
    protected $_installDepends = array();

    protected static function _getDbTables($db)
    {
        if (null === $db) {
            $db = self::getDefaultAdapter();
        }

        if (empty(self::$_tableCache)) {
            self::$_tableCache = $db->listTables();
        }

        return self::$_tableCache;
    }

    public static function clearDbTablesCache()
    {
        self::$_tableCache = array();
    }

    public function init()
    {
        $db = $this->getAdapter();
        $tables = self::_getDbTables($db);
        if (!in_array($this->_name, $tables) && !empty($this->_installSql)) {
            foreach ($this->_installDepends as $depend) {
                $temp = new $depend();
            }
            if(!is_array($this->_installSql)){
                $this->_installSql = array($this->_installSql);
            }

            foreach ($this->_installSql as $sql) {
                $stmt = $db->query($sql);
                $stmt->closeCursor();
            }
        }
    }
}