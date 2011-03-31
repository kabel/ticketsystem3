<?php

class Default_Form_Grid_Groups extends Default_Form_Grid_Abstract
{
    public function init()
    {
        $this->_validFilters = array(
            'name',
            'shortname',
        );
        $this->_sessionName = 'groups-grid';
        $this->_saveParamsInSession = true;

        return parent::init();
    }

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

        $this->_applyFilters($select);

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('config/grid/pager.phtml');
        $paginator = new Zend_Paginator($adapter);

        $this->_setPagerParams($paginator);

        return $paginator;
    }
}