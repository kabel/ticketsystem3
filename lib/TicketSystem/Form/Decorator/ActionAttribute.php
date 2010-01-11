<?php

class TicketSystem_Form_Decorator_ActionAttribute extends Zend_Form_Decorator_Abstract
{
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
            if ($attr['type'] == Default_Model_Attribute::TYPE_SELECT) {
                $options = array();
                if (!empty($attr['extra'])) {
                    $extra = Zend_Json::decode($attr['extra']);
                }
                
                if (isset($extra['src'])) {
                    if ($extra['src'] == 'user') {
                        $options += Default_Model_User::getSelectOptions();
                    } else if ($extra['src'] == 'ugroup') {
                        $options += Default_Model_Ugroup::getSelectOptions();
                    }
                } else {
                    $options += $this->_buildOptionsArray($extra['options']);
                }
                
                $attrElement = $view->formSelect($element->getName() . '_' . $attr['name'], null, null, $options);
            } else {
                $attrElement = $view->formText($element->getName() . '_' . $attr['name']);
            }
        }
        
        return $content . $separator . $attrElement;
    }
    
    public function getAttribute()
    {
        $attr = $this->getOption('attribute');
        $this->removeOption('attribute');
        
        return $attr;
    }
    
    protected function _buildOptionsArray($optionSpec)
    {
        $options = array();
        foreach ($optionSpec as $opt) {
            $options[$opt] = $opt;
        }
        
        return $options;
    }
}