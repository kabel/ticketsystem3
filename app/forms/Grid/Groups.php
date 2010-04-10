<?php

class Default_Form_Grid_Groups extends Default_Form_Grid_Abstract
{
    protected $_validFilters = array(
        'name',
        'shortname'
    );
    
    protected function _getPager()
    {
        $resource = Default_Model_Ugroup::getResourceInstance();
        
        $select2 = $resource->select()
            ->from(array('g' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), array (
                'ugroup_id',
                'count' => 'COUNT(u.`user_id`) + COUNT(m.`user_id`)'
            ))
            ->joinLeft(array('u' => 'user'), 'u.ugroup_id = g.ugroup_id', array())
            ->joinLeft(array('m' => 'membership'), 'm.ugroup_id = g.ugroup_id', array())
            ->group('g.ugroup_id');
        
        $select = $resource->select()
            ->setIntegrityCheck(false)
            ->from(array('g' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), '*')
            ->joinLeft(array('c' => $select2), 'c.ugroup_id = g.ugroup_id', array('count'));
        
        if ($filters = $this->getRequest()->getParam('filter')) {
            $filters = $this->view->filters = $this->prepareFilterString($filters);
            foreach ($filters as $col => $val) {
                if (in_array($col, $this->_validFilters)) {
                    $select->where("{$col} LIKE CONCAT('%', ?, '%')", $val);
                }
            }
        }
            
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('config/grid/pager.phtml');
        $paginator = new Zend_Paginator($adapter);
        $paginator->setView($this->view);
        
        $appSession = new Zend_Session_Namespace('TicketSystem');
        if ($this->getRequest()->getParam('ps')) {
            $pageSize = $this->getRequest()->getParam('ps');
        } elseif (isset($appSession->page_size)) {
            $pageSize = $appSession->page_size;
        } else {
            $pageSize = Default_Model_Setting::get('default_page_size');
        }
        
        $paginator->setItemCountPerPage($pageSize);
        $appSession->page_size = $pageSize;
        
        if ($pg = $this->getRequest()->getParam('pg')) {
            $paginator->setCurrentPageNumber($pg);
        }
        
        return $paginator;
    }
}