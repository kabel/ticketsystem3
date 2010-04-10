<?php

class Default_Form_Grid_Users extends Default_Form_Grid_Abstract
{
    protected $_validFilters = array(
        'username',
        'info',
        'level',
        'status'
    );
    
    protected function _getPager()
    {
        $resource = Default_Model_User::getResourceInstance();
        $select = $resource->select();
        
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