<?php

class Default_Form_Grid_Tickets_Abstract extends Default_Form_Grid_Abstract
{
    public function init()
    {
        $this->_saveParamsInSession = true;

        return parent::init();
    }

    protected function _getPager()
    {
        $this->_prepareSort();
        $search = $this->_prepareSearch();
        $select = Default_Model_Ticket::getSelectFromSearch($search, $this->view->sort, $this->view->desc);

        Zend_View_Helper_PaginationControl::setDefaultViewPartial('config/grid/pager.phtml');
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $countSelect = clone $select;
        $countSelect->reset(Zend_Db_Select::COLUMNS)->reset(Zend_Db_Select::ORDER)
            ->distinct(false)
            ->columns(array(new Zend_Db_Expr('COUNT(DISTINCT(t.ticket_id)) AS ' . Zend_Paginator_Adapter_DbSelect::ROW_COUNT_COLUMN)));
        $adapter->setRowCount($countSelect);

        $paginator = new Zend_Paginator($adapter);

        $this->_setPagerParams($paginator);

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
        $this->view->sort = $this->getParam('sort');
        $this->view->desc = ($this->getParam('desc') !== null);
    }
}