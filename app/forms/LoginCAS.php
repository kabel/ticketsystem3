<?php

class Default_Form_LoginCAS extends Zend_Form
{
    public function init()
    {
        $this->setMethod('get');
        
        $this->addElement('submit', 'login', array(
			'label' => 'Login'
        ));
    }
}