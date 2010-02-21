<?php

class Default_Form_Login extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        
        $this->addElement('text', 'username', array(
            'validators' => array(
				array('regex', false, array('/^[a-z][a-z0-9\-]+$/i')),
				array('stringLength', false, array(4, 20))
			),
			'filters' => array(
				'StringToLower',
				'StripWhitespace'
			),
			'required' => true,
			'label' => 'Username:'
        ));
        $this->getElement('username')->addPrefixPath('TicketSystem_Filter', 'TicketSystem/Filter/', 'filter');
        
        $this->addElement('password', 'passwd', array(
            'validators' => array(
				array('stringLength', false, array(4))
			),
			'required' => true,
			'label' => 'Password:'
        ));
        
        $this->addElement('submit', 'login', array(
			'label' => 'Login'
        ));
        
        $this->addElement('hash', 'csrf_login', array(
            'ignore' => true
        ));
    }
}