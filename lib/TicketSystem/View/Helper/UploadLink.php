<?php

class TicketSystem_View_Helper_UploadLink extends Zend_View_Helper_Abstract
{
    protected $_sizeNames = array(
        'B',
        'KB',
        'MB'
    );
    
    /**
     * 
     * @param Zend_Db_Table_Row_Abstract $upload
     */
    public function uploadLink($upload)
    {
        $type = str_replace(array('/', '.'), '-', str_replace(array('gif','pjpeg','jpeg','png','richtext','plain'), 'x-generic', $upload['mimetype']));
        
        if (in_array($type, array('application-octet-stream', 'application-zip', 'application-vnd-openxmlformats'))) {
            switch (substr(strrchr($upload['name'], '.'), 1)) {
                case 'xlsx':
                    $type = 'application-vnd-ms-excel';
                    break;
                case 'pptx':
                    $type = 'application-vnd-ms-powerpoint';
                    break;
                case 'docx':
                    $type = 'application-msword';
                    break;
            }
        }
        
        $output = '<a class="' . $type . '"  onclick="window.open(this.href, \'_blank\'); return false;" href="' . 
            $this->view->url(array(
                'action' => 'download',
                'controller' => 'upload',
                'id' => $upload['upload_id']
            ), 'default', true) . '">' . 
            $this->view->escape($upload['name']) . '</a>';
        
        $output .= ' (' . $this->_getOutputSize($upload['content_length']) . ')';
        
        $addedBy = array();
        
        if (!empty($upload['uploader'])) {
            $select = $upload->getTable()->select()->from('user', array('username'));
            $user = $upload->findParentRow('Default_Model_Table_User', null, $select);
            $addedBy[] = 'by <em>' . $user['username'] . '</em>';
        }
        
        if (!empty($upload['create_date'])) {
            $date = new Zend_Date($upload['create_date'], Zend_Date::ISO_8601);
            $addedBy[] = sprintf('<span title="%s">%s ago</span>', (string)$date, $this->view->timeSince($date));
        }
        
        if (!empty($addedBy)) {
            $output .= ' - added ' . implode(' ', $addedBy);
        }
        
        return $output;
    }
    
    protected function _getOutputSize($size)
    {
        if ($size > 0) {
            $exp = floor(log($size, 1024));
        } else {
            $exp = 0;
        }
        
        return sprintf('%.2f %s', $size / pow(1024, $exp), $this->_sizeNames[$exp]);
    }
}