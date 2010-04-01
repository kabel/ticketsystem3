<?php

class ReportController extends TicketSystem_Controller_ProtectedAction
{
    public function indexAction() 
    {
        $reports = Default_Model_Ticket::getReports();
        if (empty($reports)) {
            throw new Exception('Configuration files is missing a reports definition');
        }
        
        $this->view->reports = $reports;
    }
    
    public function viewAction()
    {
        if (!$id = $this->getRequest()->getParam('id')) {
            return $this->_helper->redirector('index', 'report');
        }
        
        if (!$report = Default_Model_Ticket::getReport($id)) {
            return $this->_helper->redirector('index', 'report');
        }
        
        $this->view->reports = Default_Model_Ticket::getReports();
        unset($this->view->reports[$id-1]);
        
        $appSession = new Zend_Session_Namespace('TicketSystem');
        $appSession->lastQuery = array(
            'type' => 'report',
            'url' => $this->view->url()
        );
        $sort = $this->view->sort = $this->_getParam('sort');
        $desc = $this->view->desc = $this->_hasParam('desc');
        
        $this->view->columns = $report['columns'];
        $this->view->reportName = $report['name'];
        
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
        
        if ($count) {
            $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
            $adapter->setRowCount($count);
            
            Zend_Paginator::setDefaultScrollingStyle('Sliding');
            Zend_View_Helper_PaginationControl::setDefaultViewPartial('paginator.phtml');
            $paginator = $this->view->paginator = new Zend_Paginator($adapter);
            $paginator->setView($this->view);
            
            if ($this->_hasParam('ps')) {
                $pageSize = $this->_getParam('ps');
            } elseif (isset($appSession->page_size)) {
                $pageSize = $appSession->page_size;
            } else {
                $pageSize = Default_Model_Setting::get('default_page_size');
            }
            
            $paginator->setItemCountPerPage($pageSize);
            $this->view->pageSize = $appSession->page_size = $pageSize;
            
            if ($pg = $this->_getParam('pg')) {
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
        }
    }
}

