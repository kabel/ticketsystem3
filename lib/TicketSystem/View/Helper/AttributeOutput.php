<?php

class TicketSystem_View_Helper_AttributeOutput extends Zend_View_Helper_Abstract
{
    public function attributeOutput($name, $value, $escape = true, $disableWiki = false)
    {
        if (is_numeric($name)) {
            $attr = Default_Model_Attribute::findRow($name);
        } else if (is_string($name)) {
            $attr = Default_Model_Attribute::get($name);
        } else if ($name instanceof Default_Model_Attribute) {
            $attr = $name;
        } else {
            return false;
        }
        
		if (null === $attr) {
		    return false;
		}
		
		if (!empty($attr['extra'])) {
		    $extra = Zend_Json::decode($attr['extra']);
		}
		
		switch ($attr['type']) {
		    case Default_Model_Attribute::TYPE_TEXTAREA:
            case Default_Model_Attribute::TYPE_TEXT:
        		if (isset($extra['format']) && $extra['format'] == 'wiki' && !$disableWiki) {
    		        $value = $this->view->wiki($value);
    		    } elseif ($escape) {
    		        $value = $this->view->escape($value);
    		    }
                break;
            case Default_Model_Attribute::TYPE_RADIO:
            case Default_Model_Attribute::TYPE_SELECT:
    		    if (isset($extra['src'])) {
                    if ($extra['src'] == 'user') {
                        if (is_numeric($value)) {
                            $model = Default_Model_User::findRow($value);
                            if (null === $model) {
                                $value = '--UNKNOWN--';
                            } elseif ($escape) {
                                $value = $this->view->escape((string)$model);
                            } else {
                                $value = (string)$model;
                            }
                        } else {
                            if (empty($value)) {
                                $value = 'None';
                            } elseif ($escape) {
                                $value = '<del>' . $this->view->escape($value) . '</del>';
                            } else {
                                $value = '--' . $value;
                            }
                        }
                    } else if ($extra['src'] == 'ugroup') {
                        if (is_numeric($value)) {
                            $model = Default_Model_Ugroup::findRow($value);
                            if (null === $model) {
                                $value = '--UNKNOWN--';
                            } elseif ($escape) {
                                $value = $this->view->escape((string)$model);
                            } else {
                                $value = (string)$model;
                            }
                        } else {
                            if (empty($value)) {
                                $value = 'None';
                            } elseif ($escape) {
                                $value = '<del>' . $this->view->escape($value) . '</del>';
                            } else {
                                $value = '--' . $value;
                            }
                        }
                    }
                } else {
                    if (empty($value)) {
                        $value = 'None';
                    } elseif ($escape) {
                        $value = $this->view->escape($value);
                    }
                }
                break;
		}
        
        return $value;
    }
}