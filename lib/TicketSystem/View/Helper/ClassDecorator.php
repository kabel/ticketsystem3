<?php

class TicketSystem_View_Helper_ClassDecorator extends Zend_View_Helper_Abstract
{
    public function classDecorator($class, $active=false, $i=null, $count=null)
    {
        $classes = array();
        if (!empty($class)) {
            $classes[] = $class;
        }
        
        if ($active) {
            $classes[] = 'active';
        }
        
        if ($i === 0) {
            $classes[] = 'first';
        }  else if ($i === ($count - 1)) {
            $classes[] = 'last';
        }
        
        $str_classes = implode(' ', $classes);
        
        if (empty($str_classes)) {
            return $str_classes;
        } else {
            return " class=\"$str_classes\"";
        }
    }
}