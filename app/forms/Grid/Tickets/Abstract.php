<?php

class Default_Form_Grid_Tickets_Abstract extends Default_Form_Grid_Abstract
{
    protected function _getPager()
    {
        $this->_prepareSort();
        $search = $this->_prepareSearch();
        $select = Default_Model_Ticket::getSelectFromSearch($search, $count, $this->view->sort, $this->view->desc);
        
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('config/grid/pager.phtml');
        if ($count) {
            $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
            $adapter->setRowCount($count);
            
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
            
            $ticketIds = array();
            $attributes = array(); 
            foreach ($paginator as $item) {
                $ticketIds[] = $item['ticket_id'];
                $attributes[$item['ticket_id']] = Default_Model_AttributeValue::getLatestByTicketId($item['ticket_id']);
            }
            
            $this->view->ticketsAttrs = $attributes;
            $this->view->ticketsDates = Default_Model_Changeset::getDatesByTicketId($ticketIds);
        } else {
            $adapter = new Zend_Paginator_Adapter_Array(array());
            $paginator = new Zend_Paginator($adapter);
            $paginator->setView($this->view);
        }
        
        return $paginator;
    }
    
    protected function _prepareSearch()
    {
        return array();
    }
    
    protected function _prepareSort()
    {
        $this->view->sort = $this->getRequest()->getParam('sort');
        $this->view->desc = ($this->getRequest()->getParam('desc') !== null);
    }
}