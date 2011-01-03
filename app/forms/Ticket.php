<?php

abstract class Default_Form_Ticket extends Zend_Form
{
    protected $_listValueCache = array();

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

    protected function _getSubmitLabel()
    {
        return 'Submit';
    }

    protected function _getPreviewLabel()
    {
        return 'Preview';
    }

    protected function _getCsrfPostfix()
    {
        return '';
    }

    protected function _isAclAllowed($privledge)
    {
        $user = $this->_getAuthUser();
        $acl = Zend_Registry::get('bootstrap')->getResource('acl');
        return $acl->isAllowed((string)$user->level, 'ticket', $privledge);
    }

    /**
     * Adds all the ticket attributes to a given sub-form based on form type
	 *
     * @param Zend_Form_SubForm $attrForm
     * @param string $type
     */
    protected function _addPropertiesToForm($attrForm, $formType)
    {
        $attrs = Default_Model_Attribute::getAll();
        $i = 0;
        foreach ($attrs as $name => $attr) {
            $isHidden = $attr['is_hidden'];

            if ($formType == 'new') {
                if ($name == 'description') {
                    $isHidden = false;
                }
            } elseif ($formType = 'edit') {
                if ($name == 'description' && $this->_isAclAllowed('edit-description')) {
                    $isHidden = false;
                }
            }

            if ($isHidden) {
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
                $i++;
            }
            $spec['decorators'] = $this->_getElementDecorators('properties-' . $name, $wrapperClass);
            $type = $attr['type'];

            if (!empty($attr['extra'])) {
                $extra = Zend_Json::decode($attr['extra']);
            }

            if ($formType == 'edit') {
                if ($type == Default_Model_Attribute::TYPE_TEXT &&
                    isset($extra['format']) && $extra['format'] == 'text' &&
                    isset($extra['list-acl']) && !$this->_isAclAllowed($extra['list-acl'])) {
                        $type = Default_Model_Attribute::TYPE_CHECKBOX;
                        $this->_listValueCache[$name] = $listItem = $attr->handleListValue();
                        $spec['description'] = 'Add ' . $listItem;
                }
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
        }
    }

    protected function _addButtonElements()
    {
        $this->addElement('submit', 'preview', array(
			'label' => $this->_getPreviewLabel(),
        	'decorators' => $this->_getButtonDecorators()
        ));

        $this->addElement('submit', 'save', array(
			'label' => $this->_getSubmitLabel(),
			'decorators' => $this->_getButtonDecorators()
        ));

        $this->addDisplayGroup(array('save', 'preview'), 'buttons', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div'))
            )
        ));

        $this->addElement('hash', 'csrf_ticket_' . $this->_getCsrfPostfix(), array(
            'ignore' => true,
            'decorators' => array('ViewHelper', 'Errors', array('HtmlTag', array('tag' => 'div'))),
            'timeout' => 3600
        ));
    }

    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-ticket');
        $this->setDecorators(array(
            'FormElements',
            'Form'
        ));
    }
}