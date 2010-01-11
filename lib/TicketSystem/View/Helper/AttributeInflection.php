<?php

class TicketSystem_View_Helper_AttributeInflection extends Zend_View_Helper_Abstract
{
    protected $_ticket;
    protected $_attrs;
    protected $_dates;
    
    public function attributeInflection($ticket = null, $attrs = null, $dates = null, $name = null)
    {
        if ($ticket !== null) {
            $this->_ticket = $ticket;
        }
        
        if ($attrs !== null) {
            $this->_attrs = $attrs;
        }
        
        if ($dates !== null) {
            $this->_dates = $dates;
        }
        
        if ($name !== null) {
            return $this->getValue($name);
        }
        
        return $this;
    }
    
    public function getValue($name, $isLink = false, $escape = true, $disableWiki = false)
    {
        if (null === $this->_ticket || null === $this->_attrs || null === $this->_dates) {
            throw new InvalidArgumentException();
        }
        
        if ($name[0] == '_') {
            $name = substr($name, 1);
            
            if ($name == 'created' || $name == 'modified') {
                $date = new Zend_Date($this->_dates[$name], Zend_Date::ISO_8601);
                return (string)$date;
            }
            
            if ($name == 'reporter') {
                if ($reporter = Default_Model_User::findRow($this->_ticket[$name])) {
                    if ($isLink) {
                        return $reporter['user_id'];
                    } elseif ($escape) {
                        return $this->view->escape($reporter['username']);
                    } else {
                        return  $reporter['username'];
                    }
                } else {
                    return '--UNKNOWN--';
                }
            }
            
            if (!isset($this->_ticket[$name])) {
                return '';
            }
            return $this->view->escape($this->_ticket[$name]);
        } else {
            if (empty($this->_attrs[$name])) {
                return 'None';
            } elseif ($isLink) {
                return $this->view->attributeValue($name, $this->_attrs);
            } else {
                return $this->view->attributeOutput($name, $this->_attrs[$name]['value'], $escape, $disableWiki);
            }
        }
    }
    
    public function processLinkArray($params)
    {
        $processed = array();
        foreach ($params as $param => $name) {
            $processed[$param] = $this->getValue($name, true);
        }
        
        return $processed;
    }
}