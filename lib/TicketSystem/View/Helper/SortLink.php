<?php

class TicketSystem_View_Helper_SortLink extends Zend_View_Helper_Abstract
{
    public function sortLink($sort, $desc, $idx, $label)
    {
        $class = '';
        if (empty($sort)) {
		    if ($idx == '_created') {
		        $class = ($desc) ? 'asc' : 'desc';
		    }
		} elseif ($sort == $idx) {
		    $class = ($desc) ? 'desc' : 'asc';
		}
		
        $params = array(
        	'sort' => null,
        	'desc' => null
        ); 
		if ($idx != '_created') {
		    $params['sort'] = $idx;
		}
		if ($idx == $sort && !$desc) {
		    $params['desc'] = 1;
		} elseif (empty($sort) && $idx == '_created' && !$desc) {
		    $params['desc'] = 1;
		}
		
		$url = $this->view->url($params);
		$class = (empty($class)) ? '' : ' class="' . $class . '"';
		return "<a{$class} href=\"{$url}\">" . $this->view->escape($label) . '</a>';
    }
}