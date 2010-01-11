<?php

class Default_Form_Settings extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setIsArray(true);
        $this->setName('settings');
        $this->setAttrib('class', 'form-full');
    }
    
    public function setupForSettings($settings)
    {
        if (empty($settings)) {
            throw new Exception('The array of settings for the form was empty');
        }
        
        foreach ($settings as $setting) {
            $settingGroup = new Zend_Form_SubForm(array(
                'decorators' => array('FormElements')
            ));
            $spec = array(
                'label' => $setting['name'] . ':',
                'description' => Default_Model_Setting::getHint($setting['name']),
                'decorators' => array(
                    'ViewHelper',
                    'Errors',
                    'Description',
                    array('HtmlTag', array('tag' => 'dd', 'id' => "settings-{$setting['setting_id']}-value" . "-element")),
                    array('Label', array('tag' => 'dt'))
                ),
                'prefixPath' => array('decorator' => array('TicketSystem_Form_Decorator' => 'TicketSystem/Form/Decorator/'))
            );
            $type = 'text';
            switch ($setting['type']) {
                case Default_Model_Setting::TYPE_INT:
                    $spec['required'] = true;
                    $spec['validators'] = array (
                        array('Int', true),
                        array('Between', false, array(1, 100))
                    );
                    $spec['class'] = 'int';
                    $spec['maxlength'] = 3;
                case Default_Model_Setting::TYPE_STRING:
                    $spec['value'] = $setting['value'];
                    break;
                case Default_Model_Setting::TYPE_BOOL:
                    $type = 'checkbox';
                    $spec['checked'] = (bool)$setting['value'];
                    break;
            }
            
            $settingGroup->addElement($type, 'value', $spec);
            $this->addSubForm($settingGroup, "{$setting['setting_id']}id");
        }
        
        $this->_addFields();
    }
    
    protected function _addFields()
    {
        $this->addElement('submit', 'save', array(
			'label' => 'Save'
        ));
        
        $this->addElement('hash', 'csrf_settings', array(
            'ignore' => true
        ));
    }
}