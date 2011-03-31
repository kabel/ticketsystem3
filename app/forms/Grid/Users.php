<?php

class Default_Form_Grid_Users extends Default_Form_Grid_Abstract
{
    public function init()
    {
        $this->_validFilters = array(
            'username',
            'info',
            'level',
            'status'
        );
        $this->_sessionName = 'users-grid';
        $this->_saveFiltersInSession = true;

        return parent::init();
    }

    protected function _getPager()
    {
        $resource = Default_Model_User::getResourceInstance();
        $select = $resource->select();

        $this->_applyFilters($select);

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('config/grid/pager.phtml');
        $paginator = new Zend_Paginator($adapter);

        $this->_setPagerParams($paginator);

        return $paginator;
    }
}