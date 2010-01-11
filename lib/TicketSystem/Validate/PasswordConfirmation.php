<?php

class TicketSystem_Validate_PasswordConfirmation extends Zend_Validate_Abstract
{
    const MISMATCH = 'mismatch';
    
    protected $_messageTemplates = array(
        self::MISMATCH => 'Password confirmation did not match'
    );
    
    public function isValid($value, $context=null)
    {
        $value = (string)$value;
        $this->_setValue($value);
        
        if (is_array($context)) {
            if (isset($context['passwd_new']) && ($value == $context['passwd_new']))
            {
                return true;
            }
        } else if (is_string($context) && ($value == $context)) {
            return true;
        }
        
        $this->_error(self::MISMATCH);
        return false;
    }
}