<?php

class TicketSystem_View_Helper_UploadLink extends Zend_View_Helper_Abstract
{
    public function uploadLink($upload)
    {
        $type = str_replace(array('/', '.'), '-', str_replace(array('gif','jpeg','png','richtext','plain'), 'x-generic', $upload['mimetype']));
        
        $output = '<a class="' . $type . '"  onclick="window.open(this.href, \'_blank\'); return false;" href="' . 
            $this->view->url(array(
                'action' => 'download',
                'controller' => 'upload',
                'id' => $upload['upload_id']
            ), 'default', true) . '">' . 
            $this->view->escape($upload['name']) . '</a>';
        
        return $output;
    }
}