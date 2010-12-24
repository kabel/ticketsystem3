<?php

class IndexController extends TicketSystem_Controller_ProtectedAction
{
    public function indexAction()
    {
        $appSession = new Zend_Session_Namespace('TicketSystem');
        if (isset($appSession->messages)) {
            $this->view->messages = $appSession->messages;
            unset($appSession->messages);
        }

        if ($id = Default_Model_Ticket::getDefaultReport()) {
            return $this->_forward('view', 'report', null, array('id' => $id));
        }

        return $this->_forward('index', 'report');
    }
}

