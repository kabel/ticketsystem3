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
        if ($this->isChecked() && is_array($context)) {
            if ($aa = $this->getDecorator('ActionAttribute')) {
                $attr = $aa->getAttribute();
                if ($attr instanceof Default_Model_Attribute) {
                    if ($attr['type'] == Default_Model_Attribute::TYPE_SELECT) {
                        $validator = new Zend_Validate_InArray(array_keys($attr->getMultiOptions(!$attr['is_required'])));
                    } elseif ($attr['is_required']) {
                        $validator = new Zend_Validate_NotEmpty();
                    }
                    
                    if ($validator) {
                        $innerValue = isset($context[$this->getName() . '_' . $attr['name']]) ? $context[$this->getName() . '_' . $attr['name']] : null;
                        if (!$validator->isValid($innerValue)) {
                            $this->addErrors($validator->getMessages());
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }
}
