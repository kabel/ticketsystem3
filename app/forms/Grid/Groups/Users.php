<?php

class Default_Form_Grid_Groups_Users extends Default_Form_Grid_Users
{
    protected $_sessionName = 'group-users-grid';

    protected function _getPager()
    {
        $id = $this->getRequest()->getParam('id');
        $resource = Default_Model_User::getResourceInstance();

        $select = $resource->select()
            ->from(array('u' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), '*')
            ->joinLeft(array('m' => 'membership'), 'm.user_id = u.user_id', array())
            ->where('m.ugroup_id = ? OR u.ugroup_id = ?', $id);

        $this->_applyFilters($select);

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('config/grid/pager.phtml');
        $paginator = new Zend_Paginator($adapter);
        $paginator->setView($this->view);

        $this->_setPagerParams($paginator);

        return $paginator;
    }
}