<?php

class TicketSystem_View_Helper_FormSingleRadio extends Zend_View_Helper_FormElement
{
    public function formSingleRadio($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, id, value, attribs, options, listsep, disable

        $check = false;
        if (isset($attribs['checked']) && $attribs['checked']) {
            $check = true;
            unset($attribs['checked']);
        } elseif (isset($attribs['checked'])) {
            $check = false;
            unset($attribs['checked']);
        }
        
        $checked = '';
        if ($check) {
            $checked = ' checked="checked"';
        }

        // is the element disabled?
        $disabled = '';
        if ($disable) {
            $disabled = ' disabled="disabled"';
        }

        // XHTML or HTML end tag?
        $endTag = ' />';
        if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
            $endTag= '>';
        }
        
        $pos = strpos($id, '_');
        if ($pos !== false) {
            $name = substr($name, 0, $pos);
        }

        // build the element
        $xhtml = '<input type="radio"'
                . ' name="' . $this->view->escape($name) . '"'
                . ' id="' . $this->view->escape($id) . '"'
                . ' value="' . $this->view->escape($value) . '"'
                . $checked
                . $disabled
                . $this->_htmlAttribs($attribs)
                . $endTag;

        return $xhtml;
    }
}
