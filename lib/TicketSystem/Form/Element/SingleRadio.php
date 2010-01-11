<?php

class TicketSystem_Form_Element_SingleRadio extends Zend_Form_Element_Xhtml
{
    /**
     * Use formRadio view helper by default
     * @var string
     */
    public $helper = 'formSingleRadio';
    
    public function init()
    {
        $this->addPrefixPath('TicketSystem_Form_Decorator', 'TicketSystem/Form/Decorator/', 'decorator');
    }
    
    public function isChecked()
    {
        return (isset($this->checked) && $this->checked);
    }
    
    public function isValid($value, $context = null)
    {
        return true;
    }
}
