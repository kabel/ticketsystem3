<?php

class Default_Form_Ugroup extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-full');
    }

    public function setupForGroup(Default_Model_Ugroup $group=null)
    {
        if (is_null($group) || !$group->hasData()) {
            $this->_addFields();
        } else {
            $this->_addFields(false);
            $this->populate(array(
                'name' => $group['name'],
                'shortname' => $group['shortname'],
                'notify_admin' => $group['notify_admin']
            ));
        }
    }

    protected function _addFields($isNew=true)
    {
        $this->addElement('text', 'name', array(
            'validators' => array(
				array('stringLength', false, array(4, 255))
			),
			'filters' => array(
				'StringTrim'
			),
			'required' => true,
			'label' => 'Name:'
        ));

        $this->addElement('text', 'shortname', array(
            'validators' => array(
				array('stringLength', false, array(1, 45))
			),
			'filters' => array(
				'StringTrim'
			),
			'required' => false,
			'label' => 'Shortname:'
        ));

        $this->addElement('checkbox', 'notify_admin', array(
            'label' => 'Notify Admin:',
            'required' => false,
        ));

        $buttons = array();
        if ($isNew)  {
            $this->addElement('submit', 'save', array(
    			'label' => 'Add',
            	'decorators' => $this->_getButtonDecorators()
            ));

            $buttons[] = 'save';
        } else {
            $this->addElement('submit', 'save', array(
    			'label' => 'Apply',
            	'decorators' => $this->_getButtonDecorators()
            ));

            $this->addElement('submit', 'remove', array(
    			'label' => 'Remove',
            	'decorators' => $this->_getButtonDecorators()
            ));

            $this->addElement('submit', 'reset', array(
                'label' => 'Reset',
                'decorators' => $this->_getButtonDecorators(),
                'onclick' => "window.location.href = '" . $this->getView()->url() . "'; return false;"
            ));

            $buttons += array('save', 'remove', 'reset');
        }

        $this->addElement('submit', 'cancel', array(
            'label' => 'Cancel',
            'decorators' => $this->_getButtonDecorators(),
            'onclick' => "window.location.href = '" . $this->getView()->url(array(
            	'action' => 'groups',
            	'controller' => 'config'
            ), 'default', true) . "'; return false;"
        ));

        $buttons[] = 'cancel';

        $this->addDisplayGroup($buttons, 'buttons', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div')),
                'DtDdWrapper'
            )
        ));

        $this->addElement('hash', 'csrf_group', array(
            'ignore' => true
        ));
    }

    private function _getButtonDecorators()
    {
        return array(
            'Tooltip',
            'ViewHelper'
        );
    }

    /**
     *
     * @param Zend_View_Interface $view
     * @param mixed $id
     * @return boolean
     */
    public function handlePost($view, $id)
    {
        if ($id === 'new') {
            $groupModel = new Default_Model_Ugroup();
            $this->setupForGroup();

            if (!$this->isValid($_POST)) {
                return false;
            }

            $values = $this->getValues();
            $session = new Zend_Session_Namespace('TicketSystem');

            $data = array(
                'name' => $values['name'],
                'shortname' => $values['shortname'],
                'notify_admin' => $values['notify_admin']
            );
            $groupModel->setData($data)
                ->save();

            $session->messages = array(
                'type' => 'success',
                'content' => array("Group '{$view->escape($groupModel['name'])}' successfully added")
            );
        } else {
            $groupModel = Default_Model_Ugroup::findRow($id);

            if (null === $groupModel) {
                return true;
            }

            $this->setupForGroup($groupModel);
            $view->users = $groupModel->getUsers();
            $view->membership = $groupModel->getMembership();

            if (!$this->isValid($_POST)) {
                return false;
            }

            $values = $this->getValues();
            $session = new Zend_Session_Namespace('TicketSystem');

            if (isset($values['remove'])) {
                Default_Model_AttributeValue::flattenSrc('ugroup', $groupModel->getId());
                $session->messages = array(
                    'type' => 'success',
                    'content' => array("Group '{$view->escape($groupModel['name'])}' successfully deleted")
                );
                $groupModel->delete();
            } else {
                $data = array(
                    'name' => $values['name'],
                    'shortname' => $values['shortname'],
                    'notify_admin' => $values['notify_admin']
                );

                $groupModel->setData($data)
                    ->save();
                $session->messages = array(
                    'type' => 'success',
                    'content' => array("Group '{$view->escape($groupModel['name'])}' successfully updated")
                );
            }
        }

        return true;
    }
}