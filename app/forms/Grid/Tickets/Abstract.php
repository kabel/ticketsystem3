<?php

class Default_Form_Grid_Tickets_Abstract extends Default_Form_Grid_Abstract
{
    protected function _getPager()
    {
        $this->_prepareSort();
        $search = $this->_prepareSearch();
        $select = Default_Model_Ticket::getSelectFromSearch($search, $this->view->sort, $this->view->desc);

        Zend_View_Helper_PaginationControl::setDefaultViewPartial('config/grid/pager.phtml');
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);

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
        }

        if (!empty($ticketIds)) {
            $this->view->ticketsAttrs = Default_Model_AttributeValue::getLatestByTicketIds($ticketIds);
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