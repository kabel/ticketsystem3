<?php

class TicketSystem_View_Helper_AttributeValue extends Zend_View_Helper_Abstract
{
    public function attributeValue($name, $latest, $preview = array())
    {
        if (!empty($preview['changes']) && isset($preview['changes'][$name])) {
            return $preview['changes'][$name];
        } elseif (!empty($latest[$name]) && isset($latest[$name]['value'])) {
            return $latest[$name]['value'];
        }
        
        return '';
    }
}