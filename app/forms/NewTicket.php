<?php

class Default_Form_NewTicket extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-ticket');
        $this->setAttrib('enctype', Zend_Form::ENCTYPE_MULTIPART);
        $this->setDecorators(array(
            'FormElements',
            'Form'
        ));
        
        $attrForm = new Zend_Form_SubForm();
        $attrForm->setLegend('Properties')
            ->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'dl')),
            'Fieldset'
            
        ));
        
        $attrForm->addElement('text', 'summary_', array(
            'validators' => array(array('stringLength', false, array(10, 255))),
            'required' => true,
            'label' => 'Summary:'
        ));
        
        $attrs = Default_Model_Attribute::getAll();
        
        $attr = $attrs['description'];
        $extra = Zend_Json::decode($attr['extra']);
        $attrForm->addElement(Default_Model_Attribute::getElementType($attr['type']), $attr['name'], array(
            'class' => ($extra['format'] == 'wiki') ? 'wikitext' : '',
            'required' => (bool)$attr['is_required'],
            'label' => $attr['label'] . ':',
            'decorators' => $this->_getElementDecorators('properties-' . $attr['name']),
            'prefixPath' => array('decorator' => array('TicketSystem_Form_Decorator' => 'TicketSystem/Form/Decorator/'))
        ));
        
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
            
            if (!empty($attr['extra'])) {
                $extra = Zend_Json::decode($attr['extra']);
            }
            
            switch ($attr['type']) {
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
            
            $attrForm->addElement(Default_Model_Attribute::getElementType($attr['type']), $name, $spec);
            
            $i++;
        }
        
        $this->addSubForm($attrForm, 'properties');
        
        $uploadForm = new Zend_Form_Subform();
        $uploadForm->setLegend('Uploads')
            ->setDecorators(array(
                'FormElements',
                array('HtmlTag', array('tag' => 'dl')),
                'Fieldset'
            ));
        
        $uploadForm->addElement('file', 'attachments', array(
            'label' => '',
            'decorators' => array(
                array('Description', array('tag' => 'p', 'class' => 'description')),        
                array('File', array('size' => 100, 'class' => 'block')),
                'Errors',
                array('HtmlTag', array('tag' => 'dd', 'id'  => 'uploads-attachments-element')),
                array('Label', array('tag' => 'dt'))
            ),
            'validators' => array(
                array('Count', false, array(0, 5)),
                array('Size', false, 500 * 1024),
                array('Extension', false, array('gif','jpg','jpeg','xls','xlsx','png','pdf','doc','docx','ppt','pptx','pot','pps','zip','txt','rtf','htm','shtml','html'))
            ),
            'multiFile' => 5,
            'maxFileSize' => (500 * 1024),
            'required' => false,
            'description' => 'Max file size = 500 KB' 
        ));
        
        $this->addSubForm($uploadForm, 'uploads');
        
        $this->addElement('submit', 'preview', array(
			'label' => 'Preview',
        	'decorators' => $this->_getButtonDecorators()
        ));
        
        $this->addElement('submit', 'save', array(
			'label' => 'Create Ticket',
			'decorators' => $this->_getButtonDecorators()
        ));
        
        $this->addDisplayGroup(array('save', 'preview'), 'buttons', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div'))
            )
        ));
        
        $this->addElement('hash', 'csrf_new_ticket', array(
            'ignore' => true,
            'decorators' => array('ViewHelper', 'Errors', array('HtmlTag', array('tag' => 'div')))
        ));
    }
    
    protected function _getElementDecorators($id, $class='')
    {
        return array(
            'ViewHelper',
            'Errors',
            array('Description', array('tag' => 'p', 'class' => 'description')),
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
    
    protected function _getAuthUser()
    {
        return Zend_Auth::getInstance()->getIdentity();
    }
    
    /**
     * 
     * @param Zend_View $view
     * @return mixed
     */
    public function handlePost($view)
    {
        if (!$this->isValid($_POST)) {
            return false;
        }
        
        $values = $this->getValues();
        
        if ($this->preview->isChecked()) {
            $reporter = $this->_getAuthUser()->username;
            $summary = $values['properties']['summary_'];
            $description = $values['properties']['description'];
            
            unset($values['properties']['summary_']);
            unset($values['properties']['description']);
            
            $view->preview = array(
                'summary' => $summary,
                'description' => $description,
                'reporter' => $reporter,
                'properties' => $values['properties']
            );
            
            return false;
        } else {
            $reporter = $this->_getAuthUser()->user_id;
            $ticket = new Default_Model_Ticket();
            $ticket->setData(array(
                'summary' => $values['properties']['summary_'],
                'reporter' => $reporter
            ));
            $ticket->save();
            unset($values['properties']['summary_']);
            
            $uploads = array();
            /* @var $attachments Zend_Form_Element_File */
            $attachments = $this->uploads->attachments;
            if ($attachments->isReceived()) {
                foreach ($attachments->getFileInfo() as $id => $file) {
                    if ($file['error'] == UPLOAD_ERR_OK) {
                        $content = file_get_contents($file['tmp_name']);
        				
                        //TODO: Add $file['name'] validation (only one should exists per ticket)
                        
        				$upload = new Default_Model_Upload();
        				$upload->setData(array(
        				    'name' => $file['name'],
        				    'mimetype' => $file['type'],
        				    'content_length' => $file['size'],
        				    'content' => $content,
        				    'ticket_id' => $ticket->getId()
        				));
        				$upload->save();
        				
        				$uploads[] = $file['name'];
                    }
                }
            }
            
            $changeset = new Default_Model_Changeset();
            $create_date = new Zend_Date();
            $changeset->setData(array(
                'comment' => '',
                'create_date' => $create_date->toString('YYYY-MM-dd HH:mm:ss'),
                'ticket_id' => $ticket->getId(),
                'user_id' => $reporter
            ));
            $changeset->save();
            
            $attr = Default_Model_Attribute::get('status');
            $valueModel = new Default_Model_AttributeValue();
            $valueModel->setData(array(
                'changeset_id' => $changeset->getId(),
                'attribute_id' => $attr->getId(),
                'value' => 'new'
            ));
            $valueModel->save();
            
            foreach ($values['properties'] as $name => $value) {
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
            
            /*$msgContent = array("Successfully created ticket #{$ticket->getId()}");
            if (!empty($uploads)) {
                $msgContent[] = 'with the following attachment(s): ' . implode(', ', $uploads);
            }*/
            
            $session = new Zend_Session_Namespace('TicketSystem');
            $session->messages = array(
                'type' => 'success',
                'content' => array('Successfully created ticket')
            );
            
            $latest = Default_Model_AttributeValue::getLatestByTicketId($ticket['ticket_id']);
            
            $recipients = $ticket->getNotifcationRecipients($latest);
            $notification = new Zend_Mail();
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
            
            $notification->setSubject('#' . $ticket['ticket_id'] . ': ' . $ticket['summary']);
            
            $view->clearVars();
            $view->ticket = $ticket;
            $view->latest = $latest;
            $view->description = $view->latest['description']['value'];
            
            $view->dates = array('created' => $changeset['create_date']);
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
            
            return $ticket->getId();
        }
    }
}