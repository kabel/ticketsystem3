<?php

class Default_Form_Grid_Tickets_Search extends Default_Form_Grid_Tickets_Abstract
{
    protected function _prepareSearch()
    {
        $search = array();
        foreach ($this->view->search['filters'] as $name => $filter) {
            if ($name[0] == '_') {
                $key = substr($name, 1);
            } else {
                $attr = Default_Model_Attribute::get($name);
                $key = $attr['attribute_id'];
            }
            
            if (isset($filter['mode'])) {
                $search[$key] = array(
                    'mode' => $filter['mode'],
                    'value' => $filter['filter']
                );
            } else {
                $search[$key] = $filter['filter'];
            }
        }
        
        return $search;
    }
}