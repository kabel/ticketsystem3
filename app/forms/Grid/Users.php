<?php

class Default_Form_Grid_Users extends Default_Form_Grid_Abstract
{
    protected $_sessionName = 'users-grid';
    protected $_saveParamsInSession = true;

    public function init()
    {
        $this->_validFilters = array(
            'username',
            'info',
            'level',
            'status'
        );

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