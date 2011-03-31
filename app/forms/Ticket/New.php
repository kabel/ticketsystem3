<?php

class Default_Form_Ticket_New extends Default_Form_Ticket
{
    protected function _getSubmitLabel()
    {
        return 'Create Ticket';
    }

    protected function _getCsrfPostfix()
    {
        return 'new';
    }

    public function init()
    {
        parent::init();
        $this->setAttrib('enctype', Zend_Form::ENCTYPE_MULTIPART);

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

        $this->_addPropertiesToForm($attrForm, 'new');
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
                array('Size', false, 6.3 * 1024 * 1024),
                array('Extension', false, array('gif','jpg','jpeg','xls','xlsx','png','pdf','doc','docx','ppt','pptx','pot','pps','zip','txt','rtf','htm','shtml','html'))
            ),
            'multiFile' => 5,
            'maxFileSize' => (6.3 * 1024 * 1024),
            'required' => false,
            'description' => 'Max file size = 6.3 MB'
        ));

        $this->addSubForm($uploadForm, 'uploads');

        $this->_addButtonElements();
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
            $reporter = $this->_getAuthUser()->getUsername();
            $summary = $values['properties']['summary_'];
            $description = $values['properties']['description'];

            unset($values['properties']['summary_']);
            unset($values['properties']['description']);

            $view->preview = array(
                'summary' => $summary,
                'description' => $description,
                'reporter' => $reporter,
                'changes' => $values['properties']
            );

            return false;
        } else {
            $reporter = $this->_getAuthUser()->getId();
            $ticket = new Default_Model_Ticket();
            $ticket->setData(array(
                'summary' => $values['properties']['summary_'],
                'reporter' => $reporter
            ));
            $ticket->save();
            unset($values['properties']['summary_']);

            $create_date = new Zend_Date();

            $uploads = array();
            /* @var $attachments Zend_Form_Element_File */
            $attachments = $this->uploads->attachments;
            if ($attachments->isReceived()) {
                foreach ($attachments->getFileInfo() as $id => $file) {
                    if ($file['error'] == UPLOAD_ERR_OK) {
                        $content = file_get_contents($file['tmp_name']);
                        $mime = Default_Model_Upload::detectMimeType($file);
        				$name = Default_Model_Upload::getUniqueName($file['name'], $ticket->getId());

        				$upload = new Default_Model_Upload();
        				$upload->setData(array(
        				    'name' => $name,
        				    'mimetype' => $mime,
        				    'content_length' => $file['size'],
        				    'content' => $content,
        				    'ticket_id' => $ticket->getId(),
        				    'uploader' => $reporter,
        				    'create_date' => $create_date->toString('YYYY-MM-dd HH:mm:ss')
        				));
        				$upload->save();

        				$uploads[] = $name;
                    }
                }
            }

            $changeset = new Default_Model_Changeset();
            $changeset->setData(array(
                'comment' => '',
                'create_date' => $create_date->toString('YYYY-MM-dd HH:mm:ss'),
                'ticket_id' => $ticket->getId(),
                'user_id' => $reporter
            ));
            $changeset->save();

            // insert/update dates index
            $index = new Default_Model_TicketIndexChangesetDates();
            $index->setData(array(
                'ticket_id' => $ticket->getId(),
                'type' => Default_Model_TicketIndexChangesetDates::TYPE_CREATED,
                'changeset_id' => $changeset->getId()
            ));
            $index->save();
            Default_Model_TicketIndexChangesetDates::insertUpdate($ticket->getId(), Default_Model_TicketIndexChangesetDates::TYPE_MODIFIED, $changeset->getId());

            $attr = Default_Model_Attribute::get('status');
            $valueModel = new Default_Model_AttributeValue();
            $valueModel->setData(array(
                'changeset_id' => $changeset->getId(),
                'attribute_id' => $attr->getId(),
                'value' => 'new'
            ));
            $valueModel->save();

            // update the latest index
            Default_Model_TicketIndexAttributeLatest::insertUpdate($ticket->getId(), $attr->getId(), $changeset->getId());

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

                // update the latest index
                Default_Model_TicketIndexAttributeLatest::insertUpdate($ticket->getId(), $attr->getId(), $changeset->getId());
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