<?php

class Default_Model_TicketIndexAttributeLatest extends Default_Model_Abstract
{
    protected static $_resourceNameInit = 'Default_Model_Db_TicketIndexAttributeLatest';

    /**
     *
     * @return array
     */
	public static function find()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'find'), $args);
    }

    /**
     *
     * @return Default_Model_TicketIndexAttributeLatest
     */
    public static function findRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'findRow'), $args);
    }

    /**
     *
     * @return array
     */
    public static function fetchAll()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'fetchAll'), $args);
    }

    /**
     *
     * @return Default_Model_TicketIndexAttributeLatest
     */
    public static function fetchRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'fetchRow'), $args);
    }

    public static function insertUpdate($ticket_id, $attribute_id, $changeset_id)
    {
        if (!($index = self::findRow($ticket_id, $attribute_id))) {
            $index = new self();
            $index->setData(array(
                'ticket_id' => $ticket_id,
                'attribute_id' => $attribute_id
            ));
        }

        $index->setData('changeset_id', $changeset_id);
        $index->save();
    }

    public static function rebuildIndex($ticket_ids = null)
    {
        $resource = self::getResourceInstance();
        $db = $resource->getDbTable()->getAdapter();
        $table = $resource->getDbTable()->info(Zend_Db_Table::NAME);

        $insert = "INSERT INTO " . $db->quoteIdentifier($table);
        $cols = "(" . implode(',', array('attribute_id', 'ticket_id', 'changeset_id')) . ")";
        $select = Default_Model_AttributeValue::getLatestSelect(null, $ticket_ids);

        $db->query(implode(' ', array($insert, $cols, $select)));
    }

	/**
     * Retrieve model resource
     *
     * @return Default_Model_Db_TicketIndexAttributeLatest
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }

    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
}