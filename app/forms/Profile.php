<?php

class Default_Form_Profile extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAction($this->getView()->url(array(
            'action' => 'profile',
            'controller' => 'config'
        ), null, true));
        $this->setAttrib('class', 'form-full');
        
        $user = Zend_Auth::getInstance()->getIdentity();
        if ($user->login_type != Default_Model_User::LOGIN_TYPE_CAS) {
            $this->_addOldPasswordField();
            $this->_addPasswordFields();
        }
        
        $this->_addUserFields();
        
        $this->addElement('submit', 'apply', array(
			'label' => 'Apply'
        ));
        
        $this->addElement('hash', 'csrf_profile', array(
            'ignore' => true
        ));
    }
    
    protected function _addOldPasswordField()
    {
        $this->addElement('password', 'passwd_old', array(
            'validators' => array(
                'OldPassword'
            ),
            'required' => false,
            'allowEmpty' => false,
            'label' => 'Old Password:'
        ));
        $this->getElement('passwd_old')->addPrefixPath('TicketSystem_Validate', 'TicketSystem/Validate/', 'validate');
    }
    
    protected function _addPasswordFields($isRequired=false)
    {
        $this->addElement('password', 'passwd_new', array(
            'validators' => array(
				array('stringLength', false, array(4))
			),
			'required' => $isRequired,
			'label' => ($isRequired ? '' : 'New ') . 'Password:'
        ));
        
        $this->addElement('password', 'passwd_cfm', array(
            'validators' => array(
                'PasswordConfirmation'
			),
			'required' => $isRequired,
			'allowEmpty' => false,
			'label' => 'Confirm Password:'
        ));
        $this->getElement('passwd_cfm')->addPrefixPath('TicketSystem_Validate', 'TicketSystem/Validate/', 'validate');
    }
    
    protected function _addUserFields()
    {
        $this->addElement('text', 'email', array(
            'validators' => array(
				'EmailAddress'
			),
			'required' => true,
			'label' => 'E-Mail:'
        ));
        
        $this->addElement('text', 'info', array(
            'validators' => array(
				array('StringLength', false, array(0, 255))
			),
			'required' => false,
			'label' => 'Info:'
        ));
    }
}