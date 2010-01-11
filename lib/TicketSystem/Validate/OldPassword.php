<?php

class TicketSystem_Validate_OldPassword extends Zend_Validate_Abstract
{
    const MISMATCH = 'mismatch';
    
    protected $_messageTemplates = array(
        self::MISMATCH => 'Invalid Credentials'
    );
    
    public function isValid($value, $context=null)
    {
        $value = (string)$value;
        $this->_setValue($value);
        
        if (is_array($context) && empty($context['passwd_new'])) {
            return true;
        } else {
            if ($this->_validateDbRecord($value)) {
                return true;
            }
        }
        
        $this->_error(self::MISMATCH);
        return false;
    }
    
    protected function _validateDbRecord($value)
    {
        $user = Zend_Auth::getInstance()->getIdentity();
        $db = Zend_Registry::get('bootstrap')->getResource('db');
        $clause = $db->quoteInto('user_id = ?', $user->user_id);
        
        $validator = new Zend_Validate_Db_RecordExists('user', 'passwd', $clause);
        
        return $validator->isValid(md5($value));
    }
}