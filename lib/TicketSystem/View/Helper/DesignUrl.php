<?php

class TicketSystem_View_Helper_DesignUrl extends Zend_View_Helper_Abstract
{
    protected $_theme;
    
    public function designUrl($type, $file=null)
    {
        $dir = 'skin/' . $this->getTheme() . '/' . $type;
        
        if ($file !== null) {
            $file = '/' . ltrim($file, '/\\');
        }
        
        return $this->view->baseUrl($dir . $file);
    }
    
    public function getTheme()
    {
        if (null === $this->_theme) {
            $this->_theme = Zend_Registry::get('bootstrap')->getTheme();
        }
        
        return $this->_theme;
    }
}