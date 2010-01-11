<?php

require_once 'TicketSystem/Controller/StdAction.php';

class TicketSystem_Controller_ProtectedAction extends TicketSystem_Controller_StdAction
{
    public function preDispatch()
    {
        if (!Zend_Auth::getInstance()->hasIdentity()) {
            $this->_forward('index', 'auth');
            return false;
        }
        
        if (!$this->_isAllowed((string)Zend_Auth::getInstance()->getIdentity()->level)) {
            $this->_helper->redirector('index', 'index');
        }
        return true;
    }
}