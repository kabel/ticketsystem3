<?php

require_once 'TicketSystem/Controller/StdAction.php';

class TicketSystem_Controller_ProtectedAction extends TicketSystem_Controller_StdAction
{
    public function preDispatch()
    {
        parent::preDispatch();

        if (!Zend_Auth::getInstance()->hasIdentity()) {
            $this->_forward('index', 'auth');
            return false;
        }

        // ensures the latest ACL and ban status
        $user = Default_Model_User::fetchActive();
        if (!$this->_isAllowed((string)$user['level'])) {
            $this->_helper->redirector('index', 'index');
        }
        return true;
    }
}