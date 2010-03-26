<?php

require_once 'Zend/Controller/Front.php';

class TicketSystem_Controller_EmptyAction extends Zend_Controller_Action
{   
    public function preDispatch()
    {
        $this->getResponse()->setHeader('Content-Type', 'text/html; charset=utf-8', true);
    }
    
    public function postDispatch()
    {
        $this->view->siteTitle = Default_Model_Setting::get('site_title');
        $this->view->siteBanner = Default_Model_Setting::get('site_banner');
    }
} 