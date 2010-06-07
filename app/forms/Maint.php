<?php

class Default_Form_Maint extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-full');
        
        $this->addElement('checkbox', 'purge', array(
			'label' => 'Purge Closed Tickets:',
            'description' => 'Deletes all information for tickets with a "closed" status'
        ));
        
        $this->addElement('checkbox', 'expire', array(
            'label' => 'Expire Uploads',
            'description' => 'Removes the upload content for old, closed tickets'
        ));
        
        $this->addElement('checkbox', 'tickets', array(
			'label' => 'Reset Tickets:',
            'description' => 'Removes all ticket information'
        ));
        
        $this->addElement('checkbox', 'users', array(
			'label' => 'Reset Users:',
            'description' => 'Removes all users except you and the default admin'
        ));
        
        $this->addElement('checkbox', 'reload', array(
            'label' => 'Reload Settings:',
            'description' => 'Reloads default settings that have been deleted or are missing'
        ));
        
        $this->addElement('checkbox', 'settings', array(
			'label' => 'Reset Settings:',
            'description' => 'Returns all settings to their default values'
        ));
        
        $this->addElement('submit', 'save', array(
			'label' => 'Confirm',
			'decorators' => $this->_getButtonDecorators()
        ));
        
        $this->addElement('submit', 'optimize', array(
			'label' => 'Optimize',
			'decorators' => $this->_getButtonDecorators()
        ));
        
        $this->addDisplayGroup(array('save', 'optimize'), 'buttons', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div')),
                'DtDdWrapper'
            )
        ));
        
        $this->addElement('hash', 'csrf_maint', array(
            'ignore' => true
        ));
    }
    
    private function _getButtonDecorators()
    {
        return array(
            'Tooltip',
            'ViewHelper'
        );
    }
    
    protected function _optimizeTable($tables=array())
    {
        if (!is_array($tables)) {
            $tables = array($tables);
        }
        
        $db = Zend_Registry::get('bootstrap')->getResource('db');
        
        if (empty($tables)) {
            $tables = $db->listTables();
        }
        
        $sql = 'OPTIMIZE TABLE ' . implode(', ', $tables);
        $db->exec($sql);
    }
    
    public function handlePost()
    {
        if (!$this->isValid($_POST)) {
            return false;
        }
        
        $session = new Zend_Session_Namespace('TicketSystem');
        
        if ($this->optimize->isChecked()) {
            $this->_optimizeTable();
            $session->messages = array(
                'type' => 'success',
                'content' => array('Databases successfully optimized')
            );
        } else {
            $db = Zend_Registry::get('bootstrap')->getResource('db');
            $content = array();
            
            if ($this->purge->isChecked()) {
                $tickets = Default_Model_Ticket::getClosed();
                if (!empty($tickets)) {
                    $ids = array();
                    foreach ($tickets as $ticket) {
                        $ids[] = $ticket->getId();
                    }
                    
                    $db->delete('ticket', array('ticket_id IN (?)' => $ids));
                }
                $this->_optimizeTable(array('ticket', 'attribute_value', 'changeset', 'uploads'));
                
                $content[] = 'Successfully purged closed tickets';
            }
            
            if ($this->expire->isChecked()) {
                Default_Model_Ticket::expireUploads();
                
                $content[] = 'Successfully expired uploads';
            }
            
            if ($this->tickets->isChecked()) {
                $sql = 'TRUNCATE TABLE `ticket`';
                $db->exec($sql);
                $this->_optimizeTable(array('attribute_value', 'changeset', 'uploads'));
            }
            
            if ($this->users->isChecked()) {
                $user = Zend_Auth::getInstance()->getIdentity();
                $keepIds = array($user->user_id, 1);
                Default_Model_AttributeValue::flattenSrc('user', $keepIds, true);
                $db->delete('user', array('user_id NOT IN (?)' => $keepIds));
                
                $defaultAdmin = Default_Model_User::findRow(1);
                if (empty($defaultAdmin)) {
                    $defaultAdmin = new Default_Model_User();
                    $data = array(
                        'user_id' => 1,
                        'username' => 'admin',
                        'passwd' => md5('admin'),
                        'info' => 'Administrator',
                        'email' => '',
                        'level' => Default_Model_User::LEVEL_ADMIN,
                        'login_type' => Default_Model_User::LOGIN_TYPE_LEGACY,
                        'status' => Default_Model_User::STATUS_ACTIVE
                    );
                    $defaultAdmin->setData($data)
                        ->save();
                }
                
                $this->_optimizeTable('user');
                
                $content[] = 'Users successfully reset';
            }
            
            // Either of the following actions would duplicate work, so only do one
            if ($this->settings->isChecked()) {
                Default_Model_Setting::resetDefaults();
                $content[] = 'Settings successfully reset';
            } elseif ($this->reload->isChecked()) {
                Default_Model_Setting::resetDefaults(true);
                $content[] = 'Settings successfully reloaded';
            }
            
            if (!empty($content)) {
                $session->messages = array(
                    'type' => 'success',
                    'content' => $content
                );
            }
        }
        
        return true;
    }
}