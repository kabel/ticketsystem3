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
}