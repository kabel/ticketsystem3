<?php

class ReportController extends TicketSystem_Controller_ProtectedAction
{
    public function indexAction() 
    {
        $reports = Default_Model_Ticket::getReports();
        if (empty($reports)) {
            throw new Exception('Configuration files is missing a reports definition');
        }
        
        $this->view->reports = $reports;
    }
    
    public function viewAction()
    {
        if (!$id = $this->getRequest()->getParam('id')) {
            return $this->_helper->redirector('index', 'report');
        }
        
        if (!$report = Default_Model_Ticket::getReport($id)) {
            return $this->_helper->redirector('index', 'report');
        }
        
        $this->view->reports = Default_Model_Ticket::getReports();
        unset($this->view->reports[$id-1]);
        $this->view->reportName = $report['name'];
        
        $appSession = new Zend_Session_Namespace('TicketSystem');
        $appSession->lastQuery = array(
            'type' => 'report',
            'url' => $this->view->url()
        );
        
        $form = new Default_Form_Grid_Tickets($this->view, $this->getRequest(), $report);
        if ($this->_getParam('ajax')) {
            $this->_helper->layout()->disableLayout();
            return $this->render('grid');
        }
    }
}

