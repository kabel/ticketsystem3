<?php

class TicketSystem_Form_Decorator_ActionAttribute extends Zend_Form_Decorator_Abstract
{
    protected $_attribute;
    
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }

        $separator = $this->getSeparator();
        $attr      = $this->getAttribute();
        $options   = $this->getOptions();

        if (empty($separator) && empty($attr)) {
            return $content;
        }
        
        $attrElement = '';
        if (is_string($attr)) {
            $attrElement = $attr;
        } else if ($attr instanceof Default_Model_Attribute) {
            $value = $this->getOption('value');
            if ($attr['type'] == Default_Model_Attribute::TYPE_SELECT) {
                $attrElement = $view->formSelect($element->getName() . '_' . $attr['name'], $value, null, $attr->getMultiOptions(!$attr['is_required']));
            } else {
                $attrElement = $view->formText($element->getName() . '_' . $attr['name'], $value);
            }
        }
        
        return $content . $separator . $attrElement;
    }
    
    public function getAttribute()
    {
        if (null === $this->_attribute) {
            $this->_attribute = $this->getOption('attribute');
            $this->removeOption('attribute');
        }
        
        return $this->_attribute;
    }
}