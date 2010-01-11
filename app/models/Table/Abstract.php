<?php

class Default_Model_Table_Abstract extends Zend_Db_Table_Abstract
{
    protected $_installSql = '';
    protected $_installDepends = array();
    
    public function init()
    {
        $db = $this->getAdapter();
        $tables = $db->listTables();
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