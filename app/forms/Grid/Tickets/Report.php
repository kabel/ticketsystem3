<?php

class Default_Form_Grid_Tickets_Report extends Default_Form_Grid_Tickets_Abstract
{
    protected $_report;

    protected $_sessionName = 'ticket-report-grid';

    public function __construct($view, $request, $report)
    {
        $this->_report = $report;
        parent::__construct($view, $request);
    }

    protected function _prepareSearch()
    {
        $report = $this->_report;

        $this->view->columns = $report['columns'];

        $search = array();
        if (!empty($report['search'])) {
            foreach ($report['search'] as $name => $value) {
                if ($name[0] == '_') {
                    $name = substr($name, 1);
                } elseif ($attr = Default_Model_Attribute::get($name)) {
                    $name = $attr['attribute_id'];
                } else {
                    continue;
                }

                $search[$name] = $value;
            }
        }

        return $search;
    }

    protected function _prepareSort()
    {
        $sort = null;
        $desc = null;
        if (isset($this->_report['sort'])) {
            $sort = $this->_report['sort']['by'];
            $desc = ($this->_report['sort']['desc'] !== null);
        }
        $this->view->sort = $this->getParam('sort', $sort);
        $this->view->desc = ($this->getParam('desc', $desc));
    }
}