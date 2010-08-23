<?php

class Default_Form_EditTicket extends Zend_Form
{
    protected $_actionCache = array();
    protected $_listValueCache = array();
    
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-ticket');
        $this->setDecorators(array(
            'FormElements',
            'Form'
        ));
        
        $this->addElement('textarea', 'comment', array(
            'class' => 'wikitext',
            'required' => false,
            'label' => 'Comment:',
            'decorators' => array(
                'ViewHelper',
                'Errors',
                array('Description', array('tag' => 'p', 'class' => 'description')),
                array('Label', array('separator' => '<br />')),
                array('HtmlTag', array('tag' => 'div', 'class' => 'field'))
            )
        ));
        
        $this->addDisplayGroup(array('comment'), 'comment-field', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div'))
            )
        ));
        
        $attrForm = new Zend_Form_SubForm();
        $attrForm->setLegend('Change Properties')
            ->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'dl')),
            'Fieldset'
            
        ));
        
        $attrs = Default_Model_Attribute::getAll();
        $i = 0;
        foreach ($attrs as $name => $attr) {
            if ($attr['is_hidden']) {
                continue;
            }
            
            $spec = array(
                'required' => (bool)$attr['is_required'],
            	'label' => $attr['label'] . ':',
            	'prefixPath' => array('decorator' => array('TicketSystem_Form_Decorator' => 'TicketSystem/Form/Decorator/'))
            );
            
            $wrapperClass = '';
            if ($attr['type'] == Default_Model_Attribute::TYPE_TEXTAREA) {
                $i = 0;
            } else {
                $wrapperClass = 'field-col';
                if ($i % 2 == 1) {
                    $wrapperClass .= ' noclr';
                }
            }
            $spec['decorators'] = $this->_getElementDecorators('properties-' . $name, $wrapperClass);
            $type = $attr['type'];
            
            if (!empty($attr['extra'])) {
                $extra = Zend_Json::decode($attr['extra']);
            }
            
            if ($type == Default_Model_Attribute::TYPE_TEXT && 
                isset($extra['format']) && $extra['format'] == 'text' &&
                isset($extra['list-acl']) && !$this->_isAclAllowed($extra['list-acl'])) {
                    $type = Default_Model_Attribute::TYPE_CHECKBOX;
                    $this->_listValueCache[$name] = $listItem = $attr->handleListValue();
                    $spec['description'] = 'Add ' . $listItem;
            }
            
            switch ($type) {
                case Default_Model_Attribute::TYPE_TEXTAREA:
                case Default_Model_Attribute::TYPE_TEXT:
                    $spec['class'] = ($extra['format'] == 'wiki') ? 'wikitext' : '';
                    break;
                case Default_Model_Attribute::TYPE_RADIO:
                case Default_Model_Attribute::TYPE_SELECT:
                    $options = $attr->getMultiOptions(!$attr['is_required']);
                    $spec['multiOptions'] = $options;
                    break;
            }
            
            $attrForm->addElement(Default_Model_Attribute::getElementType($type), $name, $spec);
            
            $i++;
        }
        
        $this->addSubForm($attrForm, 'properties');
    }
    
    public function prepareFromLatest($id, $latest)
    {
        $attrForm = $this->getSubForm('properties');
        $user = Zend_Auth::getInstance()->getIdentity(); 
        
        foreach ($latest as $name => $row) {
            if ($element = $attrForm->getElement($name)) {
                $element->setValue($row['value']);
                
                $attr = Default_Model_Attribute::get($name);
                if ($attr['type'] == Default_Model_Attribute::TYPE_TEXT && !empty($attr['extra'])) {
                    $extra = Zend_Json::decode($attr['extra']);
                    if (isset($extra['format']) && $extra['format'] == 'text' &&
                        isset($extra['list-acl']) && !$this->_isAclAllowed($extra['list-acl']) &&
                        Default_Model_Attribute::inList($this->_listValueCache[$name], $row['value'])) {
                            $attrForm->removeElement($name);
                    }
                }
            }
        }
        
        $this->_addActionElements($id, $latest);
        
        $this->addDisplayGroup($this->_getActionElements(), 'action', array(
            'legend' => 'Action',
            'decorators' => array(
                'FormElements',
                'Fieldset'
            )
        ));
        
        $this->_addButtonElements();
    }
    
    protected function _addActionElements($id, $latest)
    {
        $this->addPrefixPath('TicketSystem_Form_Element', 'TicketSystem/Form/Element/', 'element');
        
        $this->_actionCache['leave'] = array();
        $this->addElement('singleRadio', 'action_leave', array(
            'label' => 'leave',
            'value' => 'leave',
            'checked' => (isset($_POST['action']) && $_POST['action'] == 'leave') || (empty($_POST['action'])),
            'ignore' => true,
            'decorators' => array(
                'ViewHelper',
                array('Label', array('placement' => 'append')),
                array('ActionAttribute', array('attribute' => $latest['status']['value'], 'separator' => ' as ')),
                array('Description', array('tag' => 'span', 'class' => 'hint')),
                array('HtmlTag', array('tag' => 'div', 'class' => 'action'))
            )
        ));
        
        $actions = Zend_Registry::get('config')->actions->toArray();
        if (!empty($actions)) {
            $this->_buildChildren($actions, $id, $latest);
        }
    }
    
    protected function _buildChildren($children, $id, $latest)
    {
        foreach ($children as $type => $def) {
            if (is_int(key($def))) {
                foreach ($def as $child) {
                    $this->_buildChild($type, $child, $id, $latest);
                }
            } else {
                $this->_buildChild($type, $def, $id, $latest);
            }
        }
    }
    
    protected function _buildChild($type, $def, $id, $latest)
    {
        if ($type == 'action') {
            if (empty($def['name'])) {
                return;
            }
            
            $name = $def['name'];
            if (array_key_exists($name, $this->_actionCache)) {
                return;
            }
            $label = isset($def['label']) ? $def['label'] : $name;
            $elementSpec = array(
                'label' => $label,
                'value' => $name,
                'checked' => (isset($_POST['action']) && $_POST['action'] == $name),
                'ignore' => true
            );
            if (isset($def['order'])) {
                $elementSpec['order'] = $def['order'];
            }
            $elementDecor = array(
                'ViewHelper',
                array('Label', array('placement' => 'append'))
            );
            
            if (isset($def['actionAttribute'])) {
                $aaValid = true;
                try {
                    $aa = $def['actionAttribute'];
                    $aaSpec = array();
                    
                    if (isset($aa['separator'])) {
                        $aaSpec['separator'] = $aa['separator'];
                    }

                    if (isset($aa['prior'])) {
                        if (empty($aa['prior']['attribute'])) {
                            throw new InvalidArgumentException();
                        }
                        
                        $attr = $aa['prior']['attribute'];
                        $default = isset($aa['prior']['default']) ? $aa['prior']['default'] : null;
                        
                        if (!empty($latest[$attr])) { 
                            $attr = $this->_getPriorValue($attr, $id, $latest[$attr]['changeset_id'], $default);
                        } else {
                            $attr = $default;
                        }
                        
                        $def['actionAttribute']['prior']['cache'] = $attr;
                    } else {
                        $attr = $aa['attribute'];
                        if (is_array($attr)) {
                            if (isset($attr['model'])) {
                                $attr = Default_Model_Attribute::get($attr['model']);
                                if (null === $attr) {
                                    throw new InvalidArgumentException();
                                }
                                
                                if (isset($_POST['action_' . $name . '_' . $attr['name']])) {
                                    $aaSpec['value'] = $_POST['action_' . $name . '_' . $attr['name']];
                                }
                            } elseif (isset($attr['static'])) {
                                $attr = $attr['static'];
                            } else {
                                throw new InvalidArgumentException();
                            }
                        } else {
                            if (!empty($latest[$attr])) {
                                $attr = $latest[$attr]['value'];
                            } else {
                                $attr = '';
                            }
                        }
                    }
                    
                    $aaSpec['attribute'] = $attr;
                } catch (InvalidArgumentException $ex) {
                    $aaValid = false;
                    unset($def['actionAttribute']);
                }
                
                if ($aaValid) {
                    $elementDecor[] = array('ActionAttribute', $aaSpec);
                }
            }
            
            $elementDecor[] = array('Description', array('tag' => 'span', 'class' => 'hint'));
            if (isset($def['description'])) {
                $elementSpec['description'] = $this->_buildDescription($def['description'], $latest);
            }
            $elementDecor[] = array('Errors');
            $elementDecor[] = array('HtmlTag', array('tag' => 'div', 'class' => 'action'));
            $elementSpec['decorators'] = $elementDecor;
            
            $this->_actionCache[$name] = $def;
            $this->addElement('singleRadio', 'action_' . $name, $elementSpec);
        } elseif ($type == 'conditional') {
            if (empty($def['children']) || empty($def['value'])) {
                return;
            }
            
            $condType = isset($def['type']) ? $def['type'] : 'attribute';
            
            $value = $def['value'];
            $not = false;
            if (is_array($value) && isset($value['not'])) {
                $not = true;
                $value = $value['not'];
            }
            
            if ($condType == 'acl') {
                $cond = $this->_isAclAllowed($value);
            } elseif ($condType == 'attribute') {
                if (!isset($def['on'])) {
                    continue;
                }
                $attribute = $def['on'];
                $cond = ($latest[$attribute]['value'] == $value);
            }
            
            if ($not) {
                $cond = !$cond;
            }
            
            if ($cond) {
                $this->_buildChildren($def['children'], $id, $latest);
            } elseif (!empty($def['else'])) {
                $this->_buildChildren($def['else'], $id, $latest);
            }
        }
    }
    
    protected function _buildDescription($desc, $latest)
    {
        if (preg_match_all('/{{(\w+)}}/', $desc, $matches, PREG_SET_ORDER)) {
            $search = array();
            $replace = array();
            foreach ($matches as $match) {
                if (in_array($match[0], $search)) {
                    continue;
                }
                
                $temp = '';
                if ($match[1][0] == '_') {
                    if ($match[1] == '_auth') {
                        $temp = $this->_getAuthUser()->username;
                    }
                } elseif (isset($latest[$match[1]])) {
                    $temp = $this->getView()->attributeOutput($match[1], $latest[$match[1]]['value'], null, true, true);
                } else {
                    $temp = 'None';
                }
                
                $search[] = $match[0];
                $replace[] = $temp;
            }
            
            $desc = str_replace($search, $replace, $desc);
        }
        
        return $desc;
    }
    
    protected function _getActionElements()
    {
        $actions = array();
        foreach (array_keys($this->_actionCache) as $action) {
            $actions[] = 'action_' . $action;
        }
        
        return $actions;
    }
    
    protected function _isValidAction($action)
    {
        return array_key_exists($action, $this->_actionCache);
    }
    
    protected function _handleActionSave($action, &$changes, $latest)
    {
        $actionDef = $this->_actionCache[$action];
        
        // Auto handle ActionAttributes that use a model
        if (isset($actionDef['actionAttribute']) && 
            is_array($actionDef['actionAttribute']['attribute']) && 
            isset($actionDef['actionAttribute']['attribute']['model'])) {
                $model = $actionDef['actionAttribute']['attribute']['model'];
                $key = 'action_' . $action . '_' . $model;
                if (empty($latest[$model]) || empty($latest[$model]['value'])) {
                    if (!empty($_POST[$key])) {
                        $changes[$model] = $_POST[$key];
                    }
                } elseif ($latest[$model]['value'] != $_POST[$key]) {
                    $changes[$model] = $_POST[$key];
                }
        }
        
        if (isset($actionDef['save'])) {
            foreach ($actionDef['save'] as $attribute => $value) {
                if (is_array($value)) {
                    if (key($value) !== 'actionAttribute') {
                        continue;
                    }
                    $aa = $actionDef['actionAttribute'];
                    if (isset($aa['prior'])) {
                        $value = $aa['prior']['cache'];
                    } else {
                        $value = $actionDef['actionAttribute']['attribute'];
                        if (is_array($value)) {
                            if (isset($value['static'])) {
                                $value = $value['static'];
                            } else {
                                continue;
                            }
                        }
                    }
                } else {
                    if ($value == '{{_auth}}') {
                        $value = $this->_getAuthUser()->user_id;
                    }
                }
                
                $attr = Default_Model_Attribute::get($attribute);
                if (null !== $attr) {
                    if (empty($latest[$attribute]) || empty($latest[$attribute]['value'])) {
                        if (!empty($value)) {
                            $changes[$attribute] = $value;
                        }
                    } elseif ($latest[$attribute]['value'] != $value) {
                        $changes[$attribute] = $value;
                    }
                }
            }
        }
    }
    
    protected function _getAuthUser()
    {
        return Zend_Auth::getInstance()->getIdentity();
    }
    
    protected function _isAclAllowed($privledge)
    {
        $user = $this->_getAuthUser();
        $acl = Zend_Registry::get('bootstrap')->getResource('acl');
        return $acl->isAllowed((string)$user->level, 'ticket', $privledge);
    }
    
    protected function _addButtonElements()
    {
        $this->addElement('submit', 'preview', array(
			'label' => 'Preview',
        	'decorators' => $this->_getButtonDecorators()
        ));
        
        $this->addElement('submit', 'save', array(
			'label' => 'Submit changes',
			'decorators' => $this->_getButtonDecorators()
        ));
        
        $this->addDisplayGroup(array('save', 'preview'), 'buttons', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div'))
            )
        ));
        
        $this->addElement('hash', 'csrf_edit_ticket', array(
            'ignore' => true,
            'decorators' => array('ViewHelper', 'Errors', array('HtmlTag', array('tag' => 'div'))),
            'timeout' => 1800
        ));
    }
    
    protected function _getElementDecorators($id, $class='')
    {
        return array(
            'ViewHelper',
            'Errors',
            array('Description', array('tag' => 'span', 'class' => 'description')),
            array('HtmlTag', array('tag' => 'dd', 'id'  => $id . '-element', 'class' => $class)),
            array('Label', array('tag' => 'dt', 'class' => $class))
        );
    }
    
    protected function _getButtonDecorators()
    {
        return array(
            'Tooltip',
            'ViewHelper'
        );
    }
    
    protected function _getPriorValue($attrId, $id, $csId, $default = null) {
        if (!is_numeric($attrId)) {
            $attr = Default_Model_Attribute::get($attrId);
            $attrId = $attr['attribute_id'];
        }
        
        $prior = Default_Model_AttributeValue::getPrior($attrId, $id, $csId);
        return isset($prior['value']) ? $prior['value'] : $default;
    }
    
    /**
     * 
     * @param Zend_View_Interface $view
     * @param int $id   The ID of a ticket
     * @param array $latest
     * @return bool
     */
    public function handlePost($view, $id, $latest)
    {
        if (!$this->isValid($_POST)) {
            return false;
        }
        
        $values = $this->getValues();
        
        $changes = array();

        if ($this->_isValidAction($_POST['action'])) {
            $this->_handleActionSave($_POST['action'], $changes, $latest);
        }
        
        foreach ($values['properties'] as $name => $value) {
            $attr = Default_Model_Attribute::get($name);
            if (null === $attr) {
                continue;
            }
            
            if ($attr['type'] == Default_Model_Attribute::TYPE_TEXT && !empty($attr['extra'])) {
                $extra = Zend_Json::decode($attr['extra']);
                if (isset($extra['format']) && $extra['format'] == 'text' && 
                    isset($extra['list-acl']) && !$this->_isAclAllowed($extra['list-acl'])) {
                        if ($element = $this->properties->getElement($name)) {
                            if ($element->isChecked()) {
                                $value = $this->_listValueCache[$name];
                                if (empty($latest[$name]) || empty($latest[$name]['value'])) {
                                    $changes[$name] = $value;
                                } else {
                                    $changes[$name] = $latest[$name]['value'] . ',' . $value;
                                }
                            }
                            continue;
                        }
                }
            }
            
            if (!isset($latest[$name]) || $latest[$name]['value'] != $value) {
                $changes[$name] = $value;
            }
        }
        
        if ($this->preview->isChecked()) {
            $view->preview = array(
                'owner' => $this->_getAuthUser()->username,
                'comment' => $values['comment'],
                'changes' => $changes
            );
            
            return false;
        } else {
            if (!empty($values['comment']) || !empty($changes)) {
                $reporter = $this->_getAuthUser()->user_id;
                $create_date = new Zend_Date();
                $changeset = new Default_Model_Changeset();
                $changeset->setData(array(
                    'comment' => $values['comment'],
                    'create_date' => $create_date->toString('YYYY-MM-dd HH:mm:ss'),
                    'ticket_id' => $id,
                    'user_id' => $reporter
                ));
                $changeset->save();
                
                foreach ($changes as $name => $value) {
                    $attr = Default_Model_Attribute::get($name);
                    if (null === $attr) {
                        continue;
                    }
                    $valueModel = new Default_Model_AttributeValue();
                    $valueModel->setData(array(
                        'changeset_id' => $changeset->getId(),
                        'attribute_id' => $attr->getId(),
                        'value' => $value
                    ));
                    $valueModel->save();
                }
                
                $session = new Zend_Session_Namespace('TicketSystem');
                $session->messages = array(
                    'type' => 'success',
                    'content' => array('Successfully updated ticket')
                );
                
                $ticket = Default_Model_Ticket::findRow($id);
                $view->clearVars();
                $view->changes = array();
                $newLatest = Default_Model_AttributeValue::getLatestByTicketId($ticket['ticket_id']);
                foreach ($newLatest as $name => $row) {
                    if (isset($latest[$name])) {
                        if ($latest[$name]['value'] != $row['value']) {
                            $attr = Default_Model_Attribute::get($name);
                            $old = $view->attributeOutput($attr, $latest[$name]['value'], $id, false, true);
                            $new = $view->attributeOutput($attr, $row['value'], $id, false, true);
                            $view->changes[]  = array(
                                'label' => $attr['label'],
                                'change'  => "$old => $new"
                            );
                        }
                    } else {
                        $attr = Default_Model_Attribute::get($name);
                        $view->changes[] = array(
                        	'label' => $attr['label'],
                            'change' => $view->attributeOutput($attr, $row['value'], $id, false, true)
                        );
                    }
                }
                
                $recipients = $ticket->getNotifcationRecipients($newLatest);
                $notification = new Zend_Mail('UTF-8');
                $notification->setFrom(Default_Model_Setting::get('notification_from'));
                if (empty($recipients['to'])) {
                    if (empty($recipients['cc'])) {
                        return $ticket->getId();
                    }
                    
                    $notification->addTo(Default_Model_Setting::get('notification_from'));
                } else {
                    foreach ($recipients['to'] as $to)  {
                        $notification->addTo($to[0], $to[1]);
                    }
                }
                
                $replyTo = Default_Model_Setting::get('notification_replyto');
                if (!empty($replyTo)) {
                    $notification->setReplyTo($replyTo);
                }
                
                if (!empty($recipients['cc'])) {
                    if (Default_Model_Setting::get('use_public_cc')) {
                        $method = 'addCc';
                    } else {
                        $method = 'addBcc';
                    }
                    
                    foreach ($recipients['cc'] as $cc) {
                        call_user_func_array(array($notification, $method), $cc);
                    }
                }
                
                $notification->setSubject('RE: #' . $ticket['ticket_id'] . ': ' . $ticket['summary']);
                
                $view->ticket = $ticket;
                $view->latest = $newLatest;
                $view->author = $this->_getAuthUser()->username;
                $view->comment = $changeset['comment'];
                
                $view->dates = array('modified' => $changeset['create_date']);
                $view->staticAttrs = Default_Model_Ticket::getStaticAttrs();
                $view->attrs = Default_Model_Attribute::getAll(true);
                unset($view->attrs['description']);
                
                $colWidth = 0;
                foreach ($view->staticAttrs as $col) {
                    if (strlen($col['label']) > $colWidth) {
                        $colWidth = strlen($col['label']);
                    }
                }
                foreach (array_keys($view->dates) as $col) {
                    if (strlen($col) > $colWidth) {
                        $colWidth = strlen($col);
                    }
                }
                foreach ($view->attrs as $col) {
                    if (strlen($col['label']) > $colWidth) {
                        $colWidth = strlen($col['label']);
                    }
                }
                $view->colWidth = $colWidth;
                
                $body = $view->render('ticket/notification.phtml');
                $notification->setBodyText($body);
                
                $notification->send();
            }
        }
        
        return true;
    }
}