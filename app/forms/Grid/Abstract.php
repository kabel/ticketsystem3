<?php

class Default_Form_Grid_Abstract
{
    protected $_validFilters = array();

    protected $_sessionName = 'grid';

    protected $_saveFiltersInSession = false;

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

        return $this;
    }

    public function setSaveFiltersInSession(bool $flag)
    {
        $this->_saveFiltersInSession = $flag;

        return $this;
    }

    /**
     *
     * @return Zend_Paginator
     */
    protected function _getPager()
    {
        return null;
    }

    protected function _getFilters()
    {
        $session = new Zend_Session_Namespace('TicketSystem');
        if (!isset($session->filters)) {
            $session->filters = array();
        }
        if ($this->getRequest()->getParam('filter') !== null) {
            $filters = null;
            if ($this->getRequest()->getParam('filter') != '~') {
                $filters = $this->_prepareFilterString($this->getRequest()->getParam('filter'));
            }
            if ($this->_saveFiltersInSession) {
                $session->filters[$this->_sessionName] = $filters;
            }
            return $filters;
        } elseif ($this->_saveFiltersInSession && isset($session->filters[$this->_sessionName])) {
            $filters = $session->filters[$this->_sessionName];
            return $filters;
        }

        return null;
    }

    /**
     * Decode filter string
     *
     * @param string $filterString
     * @return data
     */
    protected function _prepareFilterString($filterString)
    {
        $data = array();
        $filterString = base64_decode($filterString);
        parse_str($filterString, $data);
        array_walk_recursive($data, array($this, '_decodeFilter'));
        return $data;
    }

    /**
     * Decode URL encoded filter value recursive callback method
     *
     * @param string $value
     */
    protected function _decodeFilter(&$value)
    {
        $value = rawurldecode($value);
    }

    /**
     *
     * @param Zend_Db_Select $select
     */
    protected function _applyFilters($select)
    {
        if ($filters = $this->_getFilters()) {
            $this->view->filters = $filters;
            foreach ($this->_validFilters as $col) {
                if (array_key_exists($col, $filters)) {
                    $select->where("{$col} LIKE CONCAT('%', ?, '%')", $filters[$col]);
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