<?php

class UploadController extends TicketSystem_Controller_ProtectedAction
{    
    public function attachAction() 
    {
        if (Default_Model_Setting::get('restrict_late_uploads')) {
            return $this->_helper->redirector('index', 'index');
        }
        
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
        
        $this->view->id = $id;
        $form = $this->view->form = new Default_Form_NewAttachment();
        
        if ($this->getRequest()->isPost()) {
            if ($form->handlePost($ticket)) {
                return $this->_helper->redirector->gotoRoute(array('id' => $id), 'ticket');
            } else {
                return;
            }
        }
    }
    
    public function downloadAction()
    {
        if (!$id = $this->_getParam('id')) {
            return $this->_helper->redirector('index', 'index');
        }
        
        $upload = Default_Model_Upload::findRow($id);
        if (null === $upload) {
            return $this->_helper->redirector('index', 'index');
        }
        
        $ticket = Default_Model_Ticket::findRow($upload['ticket_id']);
        if (!$ticket->isAllowed($ticket->getLatestAttributeValues())) {
            return $this->_helper->redirector('index', 'index');
        }
        
        $name = str_replace(' ', '_', $upload['name']);

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $response = $this->getResponse();
        $response->setHeader('Content-Length', $upload['content_length'])
            ->setHeader('Content-Type', $upload['mimetype'])
            ->setHeader('Content-Disposition', "inline; filename=$name")
            ->setBody($upload['content']);
    }
}

