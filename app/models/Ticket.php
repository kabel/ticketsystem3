<?php

class Default_Model_Ticket extends Default_Model_Abstract
{
    protected static $_resourceNameInit = 'Default_Model_Db_Ticket';
    protected static $_searchModes = array(
        ''   => array('= ?', 'IN (?)'),
        '!'  => array('!= ?', 'NOT IN (?)'),
        '^'  => array("LIKE CONCAT(?, '%')", ''),
        '$'  => array("LIKE CONCAT('%', ?)", ''),
        '~'  => array("LIKE CONCAT('%', ?, '%')", ''),
        '!~' => array("NOT LIKE CONCAT('%', ?, '%')", '')
    );
    protected static $_defaultReminderRecipients;
    protected static $_reminderGroupRecipientsCache = array();

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
     * @return Default_Model_Ticket
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
     * @return Default_Model_Ticket
     */
    public static function fetchRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'fetchRow'), $args);
    }

	/**
     * Retrieve model resource
     *
     * @return Default_Model_Db_Ticket
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }

    public static function getClosed()
    {
        $attribute = self::_getAttributeByName('status');

        $select = self::_getAttributeValueSelect($attribute['attribute_id'])
            ->where('av.value = ?', 'closed');

        return self::fetchAll($select);
    }

    public static function expireUploads()
    {
        $attribute = self::_getAttributeByName('status');

        $select = self::_getAttributeValueSelect($attribute['attribute_id'], array('ticket_id'))
            ->where('av.value = ?', 'closed');

        $ids = array();
        $tickets = self::fetchAll($select);
        foreach ($tickets as $ticket) {
            $ids[] = $ticket['ticket_id'];
        }

        if (empty($ids)) {
            return;
        }

        $ids = Default_Model_Changeset::getExpiredTicketIds($ids);

        Default_Model_Upload::expireFromTicketId($ids);
    }

    public static function getStatusCounts()
    {
        $attribute = self::_getAttributeByName('status');

        $extra = Zend_Json::decode($attribute['extra']);
        $counts = array_fill_keys($extra['options'], 0);

        $resource = self::getResourceInstance();
        $select = self::_getAttributeValueSelect($attribute['attribute_id'], 'COUNT(t.ticket_id) AS count', array('value'))
            ->group('av.value');

        $rowset = $resource->fetchAll($select);
        if (count($rowset)) {
            foreach ($rowset as $row) {
                if (array_key_exists($row['value'], $counts)) {
                    $counts[$row['value']] = $row['count'];
                }
            }
        }

        return $counts;
    }

    /**
     *
     * @param array $search
     * @param int|string $order The attribute
     * @param boolean $desc Should the attribute be in descending order
     * @param boolean $noAcl Should the ACL check be skipped
     * @return Zend_Db_Table_Select
     */
    public static function getSelectFromSearch($search, $order = null, $desc = false, $noAcl = false)
    {
        $resource = self::getResourceInstance();
        $select = self::_getSelect('*', true)->distinct();
        $joinedAttrs = array();

        if (!$noAcl) {
            $acl = Zend_Registry::get('bootstrap')->getResource('acl');
            $user = Zend_Auth::getInstance()->getIdentity();
            if (!$acl->isAllowed((string)$user->level, 'ticket', 'view-all')) {
                $perm = array();
                $permIds = array();
                $perm[] = self::_getCond('t.reporter', $user->user_id);

                $attribute = Default_Model_Attribute::get('owner');
                $permIds[] = $attribute['attribute_id'];
                $perm[] = array(
                    "(av0.attribute_id = {$attribute['attribute_id']})",
                    self::_getCond('av0.value', $user->user_id)
                );

                $attribute = Default_Model_Attribute::get('group');
                $userModel = Default_Model_User::fetchActive();
                $groupIds = $userModel->getGroupIds();
                if ($acl->isAllowed((string)$user->level, 'ticket', 'view-group') &&
                    !empty($groupIds)) {
                    $permIds[] = $attribute['attribute_id'];
                    $perm[] = array(
                        "(av0.attribute_id = {$attribute['attribute_id']})",
            			self::_getCond('av0.value', $groupIds)
                    );
                }
                self::_addAttributeValueJoin($select, $permIds, 0);
                foreach ($permIds as $key) {
                    $joinedAttrs[$key] = 0;
                }
                $select->where(self::_processWhere($perm, false));
            }
        }

        $i = 1;
        $where = array();
        foreach ($search as $key => $value) {
            if (is_numeric($key)) {
                if (array_key_exists($key, $joinedAttrs)) {
                    $j = $joinedAttrs[$key];
                } else {
                    $j = $i;
                }

                $cond = self::_getCond("av{$j}.value", $value);
                if (empty($cond)) {
                    continue;
                }

                $where[] = array(
                    "(av{$j}.attribute_id = {$key})",
                    $cond
                );
                if (!array_key_exists($key, $joinedAttrs)) {
                    self::_addAttributeValueJoin($select, $key, $i);
                    $joinedAttrs[$key] = $i;
                    $i++;
                }
            } else {
                if ($key == 'reporter') {
                    $select->joinLeft(array('u' => 'user'), 't.reporter = u.user_id', array());
                    $cond = self::_getCond('u.username', $value);
                } elseif (in_array($order, $resource->getDbTable()->info(Zend_Db_Table::COLS))) {
                    $cond = self::_getCond("t.{$key}", $value);
                } else {
                    $cond = '';
                }
                if (empty($cond)) {
                    continue;
                }
                $where[] = $cond;
            }
        }

        if (!empty($where)) {
            $select->where(self::_processWhere($where));
        }

        $select->join(array('dc' => 'ticket_index_changeset_dates'), 't.ticket_id = dc.ticket_id AND dc.type = ' . Default_Model_TicketIndexChangesetDates::TYPE_CREATED, array())
            ->join(array('c' => 'changeset'), 'dc.changeset_id = c.changeset_id', array('created' => 'create_date'))
            ->join(array('dm' => 'ticket_index_changeset_dates'), 't.ticket_id = dm.ticket_id AND dm.type = ' . Default_Model_TicketIndexChangesetDates::TYPE_MODIFIED, array())
            ->join(array('m' => 'changeset'), 'dm.changeset_id = m.changeset_id', array('modified' => 'create_date'));

        $defaultDir = 'DESC';
        if (!empty($order)) {
            if (!is_numeric($order)) {
                if ($order[0] == '_') {
                    if ($order == '_modified') {
                        $select->order('m.create_date ' . ($desc ? 'DESC' : 'ASC'));
                    } elseif ($order != '_created') {
                        $order = substr($order, 1);
                        if (in_array($order, $resource->getDbTable()->info(Zend_Db_Table::COLS))) {
                            $select->order("t.{$order} " . ($desc ? 'DESC' : 'ASC'));
                        }
                    }
                } else {
                    try{
                        $attribute = self::_getAttributeByName($order);
                        $order = $attribute['attribute_id'];
                        if (array_key_exists($order, $joinedAttrs)) {
                            $j = $joinedAttrs[$order];
                        } else {
                            self::_addAttributeValueJoin($select, $order);
                            $j = '';
                        }

                        $select->order("av{$j}.value " . ($desc ? 'DESC' : 'ASC'));
                    } catch (Exception $e) { }
                }
            } else {
                $attribute = Default_Model_Attribute::findRow($order);
                if ($attribute) {
                    if (array_key_exists($order, $joinedAttrs)) {
                        $j = $joinedAttrs[$order];
                    } else {
                        self::_addAttributeValueJoin($select, $order);
                        $j = '';
                    }
                    $select->order("av{$j}.value " . ($desc ? 'DESC' : 'ASC'));
                }
            }
        } else {
            $defaultDir = ($desc) ? 'ASC' : $defaultDir;
        }

        $select->order('c.create_date ' . $defaultDir);

        return $select;
    }

    protected static function _getCond($col, $value)
    {
        /* @var $db Zend_Db_Adapter_Abstract */
        $db = Zend_Registry::get('bootstrap')->getResource('db');

        $op = '= ?';
        if (is_array($value)) {
            $mode = '';
            if (array_key_exists('mode', $value)) {
                $mode = $value['mode'];
                if (!isset($value['value'])) {
                    return '';
                }
                $value = $value['value'];
            }
            if (!array_key_exists($mode, self::$_searchModes)) {
                return '';
            }

            if (is_array($value)) {
                if (count($value) == 1) {
                    $value = current($value);
                    $op = self::$_searchModes[$mode][0];
                } else {
                    $op = self::$_searchModes[$mode][1];
                    if (empty($op)) {
                        $op = self::$_searchModes[$mode][0];
                        $cond = array('cond' => false, 'values' => array());
                        foreach ($value as $val) {
                            $cond['values'][] = $db->quoteInto("({$col} {$op})", $val);
                        }
                        return $cond;
                    }
                }
            } else {
                $op = self::$_searchModes[$mode][0];
            }
        }

        return $db->quoteInto("({$col} {$op})", $value);
    }

    /**
     *
     * @param string|array $attributeIds OPTIONAL The attribute_id's to get the latest changeset for
     * @param string $ticketCol OPTIONAL The column/expression to select from the ticket table
     * @param array $avCols OPTIONAL The columns to select from the attribute_value table
     * @return Zend_Db_Table_Select
     */
    protected static function _getAttributeValueSelect($attributeIds = null, $ticketCol = '*', $avCols = array())
    {
        $select = self::_getSelect($ticketCol);
        self::_addAttributeValueJoin($select, $attributeIds, '', $avCols);

        return $select;
    }

    /**
     *
     * @param string $ticketCol [optional] The column/expression to select from the ticket table
     * @return Zend_Db_Table_Select
     */
    protected static function _getSelect($ticketCol = '*', $disableItegrity = false)
    {
        $resource = self::getResourceInstance();
        $select = $resource->select();

        if ($ticketCol !== '*' || $disableItegrity) {
            $select->setIntegrityCheck(false);
        }

        $select->from(array('t' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), $ticketCol);

        return $select;
    }

    /**
     *
     * @param Zend_Db_Table_Select $select
     * @param string|array $attributeIds
     * @param mixed $i [optional] The suffix for the join aliases
     * @param array $avCols [optional]
     * @return Zend_Db_Table_Select
     */
    protected static function _addAttributeValueJoin($select, $attributeIds, $i = '', $avCols = array())
    {
        if (!is_array($attributeIds)) {
            $attributeIds = array($attributeIds);
        }
        $joinCond = $select->getAdapter()->quoteInto(" AND lv{$i}.attribute_id IN (?)", $attributeIds);

        $select->joinLeft(array("lv{$i}" => 'ticket_index_attribute_latest'),
            	"lv{$i}.ticket_id = t.ticket_id" . $joinCond, array())
            ->joinLeft(array("av{$i}" => 'attribute_value'),
            	"av{$i}.changeset_id = lv{$i}.changeset_id AND av{$i}.attribute_id = lv{$i}.attribute_id", $avCols);
    }

    /**
     *
     * @param string $name The name of the attribute
     * @return Default_Model_Attribute
     */
    protected static function _getAttributeByName($name)
    {
        $attribute = Default_Model_Attribute::get($name);
        if (empty($attribute)) {
            throw new Exception('Attribute "' . $name . '" could not be found');
        }

        return $attribute;
    }

    public static function getReports()
    {
        $reports = Zend_Registry::get('config')->reports->toArray();
        if (empty($reports['report'])) {
            return array();
        } else {
            return $reports['report'];
        }
    }

    public static function getReport($id)
    {
        if ($id < 1) {
            return false;
        }

        $reports = self::getReports();
        if (empty($reports[$id - 1])) {
            return false;
        }

        return $reports[$id - 1];
    }

    public static function getDefaultReport()
    {
        $reports = self::getReports();
        foreach ($reports as $i => $report) {
            if (isset($report['default'])) {
                return $i + 1;
            }
        }

        return false;
    }

    public static function getStaticAttrs()
    {
        $staticMap = array(
            'ticket_id' => array(
            	'label' => 'Id',
            	'name' => 'ticket_id'
            )
        );

        $staticAttrs = array();
        $ticketCols = self::getResourceInstance()->getDbTable()->info(Zend_Db_Table::COLS);
        foreach ($ticketCols as $attr) {
            if (array_key_exists($attr, $staticMap)) {
                $staticAttrs[$attr] = $staticMap[$attr];
            } else {
                $staticAttrs[$attr] = array(
                    'label' => ucfirst($attr),
                    'name' => $attr
                );
            }
        }

        return $staticAttrs;
    }

    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }

    /**
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getUploads()
    {
        $select = $this->getResource()->select()->from('upload', array('upload_id', 'name', 'mimetype', 'content_length', 'create_date', 'uploader', 'expired_date'));
        return parent::findDependents('Default_Model_Table_Upload', null,  $select);
    }

    /**
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getChangesets()
    {
        $select = $this->getResource()->select()->order('changeset_id');
        return parent::findDependents('Default_Model_Table_Changeset', null, $select);
    }

    /**
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getReporter()
    {
        return parent::findParent('Default_Model_Table_User');
    }

    public function getLatestAttributeValues()
    {
        return Default_Model_AttributeValue::getLatestByTicketId($this->getId());
    }

    public function isAllowed($latest)
    {
        $user = Zend_Auth::getInstance()->getIdentity();
        $result = true;

        $acl = Zend_Registry::get('bootstrap')->getResource('acl');
        if (!$acl->isAllowed((string)$user->level, 'ticket', 'view-all')) {
            $result = false;
            if ($this['reporter'] == $user->user_id ||
                (isset($latest['owner']) && $latest['owner']['value'] == $user->user_id)) {
                return true;
            }

            if ($acl->isAllowed((string)$user->level, 'ticket', 'view-group') &&
                !empty($user->ugroup_id) && $latest['group']['value'] == $user->ugroup_id) {
                return true;
            }
        }

        return $result;
    }

    public function getNotifcationRecipients($latest, $updater = false)
    {
        $recipients = array(
            'to' => array(),
            'cc' => array()
        );

        if (!empty($this['reporter']) && Default_Model_Setting::get('always_notify_reporter')) {
            $user = Default_Model_User::findRow($this['reporter']);
            if (null !== $user && !empty($user['email'])) {
                $recipients['to'][] = array($user['email'], $user['info']);
            }
        }

        if (isset($latest['owner']) && is_numeric($latest['owner']['value']) && Default_Model_Setting::get('always_notify_owner')) {
            $user = Default_Model_User::findRow($latest['owner']['value']);
            if (null !== $user && !empty($user['email'])) {
                $recipients['to'][] = array($user['email'], $user['info']);
            }
        }

        if ($updater && Default_Model_Setting::get('always_notify_updater')) {
            $user = Zend_Auth::getInstance()->getIdentity();
            if (!empty($user->email)) {
                $recipients['to'][] = array($user->email, $user->info);
            }
        }

        if (isset($latest['cc']) && !empty($latest['cc']['value'])) {
            $recipients['cc'] = array_merge($recipients['cc'], Default_Model_User::prepareCc($latest['cc']['value']));
        }

        $globalCc = Default_Model_Setting::get('global_cc');
        if (!empty($globalCc)) {
            $recipients['cc'] = array_merge($recipients['cc'], Default_Model_User::prepareCc(Default_Model_Setting::get('global_cc')));
        }

        return $recipients;
    }

    public static function getReminderRecipients($latest)
    {
        $recipients = array();

        if (!empty($latest['group']['value'])) {
            if (isset(self::$_reminderGroupRecipientsCache[$latest['group']['value']])) {
                $recipients = self::$_reminderGroupRecipientsCache[$latest['group']['value']];
            } else {
                $group = Default_Model_Ugroup::findRow($latest['group']['value']);
                $users = array(
                    'users' => $group->getUsers(),
                    'members' => $group->getMembership()
                );
                foreach ($users as $rowset) {
                    foreach ($rowset as $user) {
                        if ($user['level'] == Default_Model_User::LEVEL_MODERATOR
                            && $user['status'] == Default_Model_User::STATUS_ACTIVE && !empty($user['email'])) {
                            $recipients[] = array($user['email'], $user['info']);
                        }
                    }
                }

                self::$_reminderGroupRecipientsCache[$latest['group']['value']] = $recipients;
            }
        }

        if (empty($recipients)) {
            $recipients = self::_getDefaultReminderRecipients();
        }

        return $recipients;
    }

    protected static function _getDefaultReminderRecipients()
    {
        if (empty(self::$_defaultReminderRecipients)) {
            $recipients = array();
            $users = Default_Model_User::fetchAll(array(
                'status = ?' => Default_Model_User::STATUS_ACTIVE,
                'level = ?' => Default_Model_User::LEVEL_ADMIN,
                'email != ?' => ''
            ));

            foreach ($users as $user) {
                $recipients[] = array($user['email'], $user['info']);
            }
            self::$_defaultReminderRecipients = $recipients;
        }

        return self::$_defaultReminderRecipients;
    }
}