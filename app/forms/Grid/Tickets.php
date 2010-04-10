<?php

class Default_Form_Grid_Tickets extends Default_Form_Grid_Abstract
{
    protected $_report;
    
    public function __construct($view, $request, $report)
    {
        $this->_report = $report;
        parent::__construct($view, $request);
    }
    
    protected function _getPager()
    {
        $report = $this->_report;
        
        $sort = $this->view->sort = $this->getRequest()->getParam('sort');
        $desc = $this->view->desc = ($this->getRequest()->getParam('desc') !== null);
        
        $this->view->columns = $report['columns'];
        
        $search = array();
        if (!empty($report['search'])) {
            foreach ($report['search'] as $name => $value) {
                if ($name[0] == '_') {
                    $name = substr($name, 1);
                } elseif ($attr = Default_Model_Attribute::get($name)) {
                    $name = $attr['attribute_id'];
                } else {
                    continue;
                }
                
                $search[$name] = $value;
            }
        }
        
        $select = Default_Model_Ticket::getSelectFromSearch($search, $count, $sort, $desc);
        
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
}