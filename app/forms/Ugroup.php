<?php

class Default_Form_Ugroup extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-full');
    }
    
    public function setupForGroup(Default_Model_Ugroup $group=null)
    {
        if (is_null($group) || !$group->hasData()) {
            $this->_addFields();
        } else {
            $this->_addFields(false);
            $this->populate(array(
                'name' => $group['name'],
                'shortname' => $group['shortname']
            ));
        }
    }
    
    protected function _addFields($isNew=true)
    {
        $this->addElement('text', 'name', array(
            'validators' => array(
				array('stringLength', false, array(4, 255))
			),
			'filters' => array(
				'StringTrim'
			),
			'required' => true,
			'label' => 'Name:'
        ));
        
        $this->addElement('text', 'shortname', array(
            'validators' => array(
				array('stringLength', false, array(1, 45))
			),
			'filters' => array(
				'StringTrim'
			),
			'required' => false,
			'label' => 'Shortname:'
        ));
        
        $buttons = array();
        if ($isNew)  {
            $this->addElement('submit', 'save', array(
    			'label' => 'Add',
            	'decorators' => $this->_getButtonDecorators()
            ));
            
            $buttons[] = 'save';
        } else {
            $this->addElement('submit', 'save', array(
    			'label' => 'Apply',
            	'decorators' => $this->_getButtonDecorators()
            ));
            
            $this->addElement('submit', 'remove', array(
    			'label' => 'Remove',
            	'decorators' => $this->_getButtonDecorators()
            ));
            
            $this->addElement('submit', 'reset', array(
                'label' => 'Reset',
                'decorators' => $this->_getButtonDecorators(),
                'onclick' => "window.location.href = '" . $this->getView()->url() . "'; return false;"
            ));
            
            $buttons += array('save', 'remove', 'reset');
        }
        
        $this->addElement('submit', 'cancel', array(
            'label' => 'Cancel',
            'decorators' => $this->_getButtonDecorators(),
            'onclick' => "window.location.href = '" . $this->getView()->url(array(
            	'action' => 'groups', 
            	'controller' => 'config'
            ), 'default', true) . "'; return false;"
        ));
        
        $buttons[] = 'cancel';
        
        $this->addDisplayGroup($buttons, 'buttons', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div')),
                'DtDdWrapper'
            )
        ));
        
        $this->addElement('hash', 'csrf_group', array(
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