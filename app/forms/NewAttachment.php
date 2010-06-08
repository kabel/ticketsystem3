<?php

class Default_Form_NewAttachment extends Zend_Form
{
    public function init()
    {
        $view = $this->getView();
        $this->setMethod('post');
        $this->setAttrib('class', 'form-ticket');
        $this->setAttrib('enctype', Zend_Form::ENCTYPE_MULTIPART);
        
        $this->addElement('file', 'attachment', array(
            'label' => 'File:',
            'decorators' => array(        
                array('File', array('size' => 100, 'class' => 'block')),
                'Errors',
                array('Description', array('tag' => 'p', 'class' => 'description')),
                array('HtmlTag', array('tag' => 'dd', 'id'  => 'attachment-element')),
                array('Label', array('tag' => 'dt'))
            ),
            'validators' => array(
                array('Size', false, 6.3 * 1024 * 1024),
                array('Extension', false, array('gif','jpg','jpeg','xls','xlsx','png','pdf','doc','docx','ppt','pptx','pot','pps','zip','txt','rtf','htm','shtml','html'))
            ),
            'maxFileSize' => (6.3 * 1024 * 1024),
            'required' => true,
            'description' => 'size limit 6.3 MB'
        ));
        
        $this->addElement('submit', 'save', array(
			'label' => 'Add Attachment',
			'decorators' => $this->_getButtonDecorators()
        ));
        
        $this->addElement('submit', 'cancel', array(
            'label' => 'Cancel',
            'decorators' => $this->_getButtonDecorators(),
            'onclick' => "window.location.href = '" . $view->url(array('id' => $view->id), 'ticket', true) . "'; return false;"
        ));
        
        $this->addDisplayGroup(array('save', 'cancel'), 'buttons', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div')),
                'DtDdWrapper'
            )
        ));
        
        $this->addElement('hash', 'csrf_new_attachment', array(
            'ignore' => true
        ));
    }
    
    protected function _getButtonDecorators()
    {
        return array(
            'Tooltip',
            'ViewHelper'
        );
    }
    
    public function handlePost($ticket)
    {
        if (!$this->isValid($_POST)) {
            return false;
        }
                
        $values = $this->getValues();
        
        if ($this->cancel->isChecked()) {
            return -1;
        } else {
            $attachment = $this->attachment;
            if ($attachment->isReceived()) {
                $file = current($attachment->getFileInfo());
                $create_date = new Zend_Date();
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
				    'uploader' => Zend_Auth::getInstance()->getIdentity()->user_id,
				    'create_date' => $create_date->toString('YYYY-MM-dd HH:mm:ss')
				));
				$upload->save();
            } else {
                return false;
            }
            
            $session = new Zend_Session_Namespace('TicketSystem');
            $session->messages = array(
                'type' => 'success',
                'content' => array("Successfully uploaded '{$name}'")
            );
                        
            return true;
        }
    }
}