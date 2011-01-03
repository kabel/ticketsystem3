<?php

class Default_Form_Grid_Abstract
{
    protected $_validFilters = array();

    /**
     *
     * @var Zend_View_Abstract
     */
    protected $view;

    /**
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    public function __construct($view, $request)
    {
        $this->view = $view;
        $this->request = $request;

        $this->init();
    }

    /**
     *
     * @return Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function init()
    {
        $this->view->paginator = $this->_getPager();
    }

    /**
     *
     * @return Zend_Paginator
     */
    protected function _getPager()
    {
        return null;
    }

    /**
     * Decode filter string
     *
     * @param string $filterString
     * @return data
     */
    protected function prepareFilterString($filterString)
    {
        $data = array();
        $filterString = base64_decode($filterString);
        parse_str($filterString, $data);
        array_walk_recursive($data, array($this, 'decodeFilter'));
        return $data;
    }

    /**
     * Decode URL encoded filter value recursive callback method
     *
     * @param string $value
     */
    protected function decodeFilter(&$value)
    {
        $value = rawurldecode($value);
    }

    /**
     *
     * @param Zend_Db_Select $select
     */
    protected function _applyFilters($select)
    {
        if ($filters = $this->getRequest()->getParam('filter')) {
            $filters = $this->view->filters = $this->prepareFilterString($filters);
            foreach ($filters as $col => $val) {
                if (in_array($col, $this->_validFilters)) {
                    $select->where("{$col} LIKE CONCAT('%', ?, '%')", $val);
                }
            }
        }
    }

    /**
     *
     * @param Zend_Paginator $paginator
     */
    protected function _setPagerParams($paginator)
    {
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
    }
}