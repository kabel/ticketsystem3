<?php

class TicketController extends TicketSystem_Controller_ProtectedAction
{    
    public function newAction()
    {
        $form = $this->view->form = new Default_Form_NewTicket();
        
        if ($this->getRequest()->isPost()) {
            if ($id = $form->handlePost($this->view)) {
                return $this->_helper->redirector->gotoRoute(array('id' => $id), 'ticket');
            } else {
                return;
            }
        }
    }
    
    protected function _initSearch()
    {
        $this->view->attrs = Default_Model_Attribute::getAll();
        $staticAttrs = Default_Model_Ticket::getStaticAttrs();
        unset($staticAttrs['ticket_id']);
        $this->view->staticAttrs = $staticAttrs;
        $this->view->extraCols = array('created', 'modified');
    }
    
    public function searchAction()
    {
        $appSession = new Zend_Session_Namespace('TicketSystem');
        $this->_initSearch();
        
        if (isset($_POST['update'])) {
            $cols = array();
            if (!empty($_POST['cols'])) {
                foreach ($_POST['cols'] as $col) {
                    if ($col[0] == '_') {
                        if (array_key_exists(substr($col, 1), $this->view->staticAttrs) || in_array(substr($col, 1), $this->view->extraCols)) {
                            $cols[] = $col;
                        }
                    } elseif (array_key_exists($col, $this->view->attrs)) {
                        $cols[] = $col;
                    }
                }
            }
            $filters = array();
            if (!empty($_POST['filters'])) {
                foreach ($_POST['filters']  as $name => $filter) {
                    if ($name[0] == '_') {
                        if (array_key_exists(substr($name, 1), $this->view->staticAttrs)) {
                            $filters[$name] = $filter;
                        }
                    } elseif (array_key_exists($name, $this->view->attrs)) {
                        $filters[$name] = $filter;
                    }
                }
            }
            $appSession->search = array(
                'filters' => $filters,
                'cols' => $cols
            );
            $this->_helper->redirector('results', 'ticket');
        }
        
        unset($appSession->search);
    }
    
    public function resultsAction()
    {
        $appSession = new Zend_Session_Namespace('TicketSystem');
        if (!isset($appSession->search)) {
            $this->_helper->redirector('search', 'ticket');
        }
        
        $this->_initSearch();
        $this->view->search = $appSession->search;
        
        $appSession->lastQuery = array(
            'type' => 'search results',
            'url' => $this->view->url()
        );
        
        $form = new Default_Form_Grid_Tickets_Search($this->view, $this->getRequest());
        if ($this->_getParam('ajax')) {
            $this->_helper->layout()->disableLayout();
            return $this->render('resultsGrid');
        }
    }
    
    public function viewAction()
    {
        if (!$id = $this->_getParam('id')) {
            return $this->_helper->redirector('index', 'index');
        }
        
        $ticket = Default_Model_Ticket::findRow($id);
        if (null === $ticket) {
            return $this->_helper->redirector('index', 'index');
        }
        
        $latest = $ticket->getLatestAttributeValues();
        if (!$ticket->isAllowed($latest)) {
            return $this->_helper->redirector('index', 'index');
        }
        
        $appSession = new Zend_Session_Namespace('TicketSystem');
        if (isset($appSession->messages)) {
            $this->view->messages = $appSession->messages;
            unset($appSession->messages);
        }
        if (isset($appSession->lastQuery)) {
            $this->view->returnUrl = $appSession->lastQuery;
        } else {
            $this->view->returnUrl = array(
                'type' => 'report',
                'url' => $this->view->url(array('id' => 1), 'report', true)
            );
        }
        
        $this->view->ticket = array(
            'self' => $ticket,
            'reporter' => $ticket->getReporter(),
            'latest' => $latest,
            'changesets' => $ticket->getChangesets(),
            'uploads' => $ticket->getUploads()
        );
        
        $form = $this->view->form = new Default_Form_EditTicket();
        $form->prepareFromLatest($id, $latest);
        
        if ($this->getRequest()->isPost()) {
            if ($form->handlePost($this->view, $id, $latest)) {
                return $this->_helper->redirector->gotoRoute(array('id' => $id), 'ticket');
            } else {
                return;
            }
        }
    }
    
    protected function _isAllowed($userLevel)
    {
        $result = false;
        switch ($this->getRequest()->getActionName()) {
            case 'new':
                $result = $this->_isAclAllowed($userLevel, 'ticket', 'create');
                if (!$result) {
                    $appSession = new Zend_Session_Namespace('TicketSystem');
                    $appSession->messages = array (
                        'type' => 'notice',
                        'content' => array('Guest users are configured to not be able to create tickets.')
                    );
                }
                break;
            default:
                $result = parent::_isAllowed($userLevel);
        }
        
        return $result;
    }
}