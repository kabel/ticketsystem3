<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../app'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'prod'));

// Ensure lib/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../lib'),
	get_include_path()
)));

require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();

$application = new Zend_Application(
    APPLICATION_ENV, 
    APPLICATION_PATH . '/etc/config.xml'
);

$bootstrap = $application->getBootstrap();
$bootstrap->bootstrap('db');
$bootstrap->bootstrap('autoload');

$oldDbSettings = array();
echo 'Please enter the settings for the old DB' . PHP_EOL;
echo 'Host: ';
$oldDbSettings['host'] = trim(fgets(STDIN));
echo 'Username: ';
$oldDbSettings['username'] = trim(fgets(STDIN));
echo 'Password: ';
$oldDbSettings['password'] = trim(fgets(STDIN));
echo 'Database: ';
$oldDbSettings['dbname'] = trim(fgets(STDIN));

$oldDb = Zend_Db::factory('Pdo_Mysql', $oldDbSettings);
$db = $bootstrap->getResource('db');
/* @var $db Zend_Db_Adapter_Abstract */

$processed = array(
    'tickets' => array(),
    'users' => array(1 => 1),
    'groups' => array()
);

try {
    $select = $oldDb->select()->from('ticket');
    $stmt = $oldDb->query($select);
    $result = $stmt->fetchAll();
    if (!empty($result)) {
        foreach ($result  as $row) {
            processTicket($row, $processed, $oldDb);
        }
    }
    
    $select = $oldDb->select()->from('user');
    $stmt = $oldDb->query($select);
    $result = $stmt->fetchAll();
    if (!empty($result)) {
        foreach ($result  as $row) {
            processUser($row, $processed, $oldDb);
        }
    }
    
    $select = $oldDb->select()->from('ugroup');
    $stmt = $oldDb->query($select);
    $result = $stmt->fetchAll();
    if (!empty($result)) {
        foreach ($result  as $row) {
            processGroup($row, $processed, $oldDb);
        }
    }
} catch (Exception $e) {
    echo 'AN ERROR HAS OCCURED:' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    return false;
}

return true;

function processTicket($ticket, &$processed, $oldDb)
{
    if (array_key_exists($ticket['id'], $processed['tickets']))  {
        echo 'Already processed ticket #' . $ticket['id'] . PHP_EOL;
        return $processed['tickets'][$ticket['id']];
    }
    
    $data = array('summary' => html_entity_decode($ticket['affected']));
    $reporter = processUser($ticket['owner'], $processed, $oldDb);
    $data['reporter'] = $reporter;
    
    $ticketModel = new Default_Model_Ticket();
    $ticketModel->setData($data);
    try {
        $ticketModel->save();
    } catch (Exception $c) {
        return null;
    }
    
    $newId = $ticketModel->getId();
    $processed['tickets'][$ticket['id']] = $newId;
    
    $date = new Zend_Date($ticket['date'], Zend_Date::ISO_8601);
    $data = array(
        'comment' => '',
        'create_date' => $date->toString('YYYY-MM-dd HH:mm:ss'),
        'ticket_id' => $newId,
        'user_id' => $reporter
    );
    $changeset = new Default_Model_Changeset();
    $changeset->setData($data);
    $changeset->save();
    
    Default_Model_Attribute::getAll();
    
    $attr = Default_Model_Attribute::get('description');
    $attrVal = new Default_Model_AttributeValue();
    $attrVal->setData(array(
        'changeset_id' => $changeset['changeset_id'],
        'attribute_id' => $attr['attribute_id'],
        'value' => html_entity_decode($ticket['description'])
    ));
    $attrVal->save();
    
    $attr = Default_Model_Attribute::get('status');
    $attrVal = new Default_Model_AttributeValue();
    $attrVal->setData(array(
        'changeset_id' => $changeset['changeset_id'],
        'attribute_id' => $attr['attribute_id'],
        'value' => 'new'
    ));
    $attrVal->save();
    
    $attr = Default_Model_Attribute::get('priority');
    $attrVal = new Default_Model_AttributeValue();
    $attrVal->setData(array(
        'changeset_id' => $changeset['changeset_id'],
        'attribute_id' => $attr['attribute_id'],
        'value' => ($ticket['severity'] == 2) ? 'major' : 'minor'
    ));
    $attrVal->save();
    
    $group = processGroup($ticket['group_id'], $processed, $oldDb);
    $attr = Default_Model_Attribute::get('group');
    $attrVal = new Default_Model_AttributeValue();
    $attrVal->setData(array(
        'changeset_id' => $changeset['changeset_id'],
        'attribute_id' => $attr['attribute_id'],
        'value' => (null === $group) ? '' : $group
    ));
    $attrVal->save();
    
    copyUploads($ticket['id'], $newId, $oldDb);
    if ($lastDate = copyActions($ticket['id'], $newId, $processed, $oldDb)) {
        $date = new Zend_Date($lastDate, Zend_Date::ISO_8601);
    }
    $date->addSecond(1);
    
    if ($ticket['status'] == 1) {
        $changeset = new Default_Model_Changeset();
        $changeset->setData(array(
            'comment' => '',
            'create_date' => $date->toString('YYYY-MM-dd HH:mm:ss'),
            'ticket_id' => $newId,
            'user_id' => 1
        ));
        $changeset->save();
        
        $attr = Default_Model_Attribute::get('status');
        $attrVal = new Default_Model_AttributeValue();
        $attrVal->setData(array(
            'changeset_id' => $changeset['changeset_id'],
            'attribute_id' => $attr['attribute_id'],
            'value' => 'closed'
        ));
        $attrVal->save();
        
        $attr = Default_Model_Attribute::get('resolution');
        $attrVal = new Default_Model_AttributeValue();
        $attrVal->setData(array(
            'changeset_id' => $changeset['changeset_id'],
            'attribute_id' => $attr['attribute_id'],
            'value' => 'fixed'
        ));
        $attrVal->save();
    }
    
    echo "Processed ticket #{$ticket['id']} => {$newId}" . PHP_EOL;
    return $newId;
}

/**
 * 
 * @param array|int $user
 * @param array $processed
 * @param Zend_Db_Adapter_Abstract $oldDb
 * @return int|null
 */
function processUser($user, &$processed, $oldDb)
{
    if (empty($user)) {
        return null;
    }
    
    if (!is_array($user)) {
        if (array_key_exists($user, $processed['users'])) {
            return $processed['users'][$user];
        }
        $select = $oldDb->select()->from('user')->where('id = ?', $user);
        $stmt = $oldDb->query($select);
        $row = $stmt->fetch();
        if (!empty($row)) {
            $user = $row;
        } else {
            return null;
        }
    }
    
    if (array_key_exists($user['id'], $processed['users'])) {
        return $processed['users'][$user['id']];
    }
    
    $username = strtolower(str_replace(' ', '', $user['user']));
    $data = array(
        'username' => $username,
        'passwd' => (null === $user['passwd']) ? '' : $user['passwd'],
        'info' => $user['info'],
        'email' => $user['email'],
        'level' =>  $user['level']
    );
    
    if ($user['login_type'] == 3) {
        $data['login_type'] = Default_Model_User::LOGIN_TYPE_CAS;
        $data['status'] = Default_Model_User::STATUS_BANNED;
    } else {
        $data['login_type'] = $user['login_type'];
        $data['status'] = Default_Model_User::STATUS_ACTIVE;
    }
    
    $data['ugroup_id'] = processGroup($user['group_id'], $processed, $oldDb);
    
    $userModel = new Default_Model_User();
    $userModel->setData($data);
    try {
        $userModel->save();
        $newId = $userModel['user_id'];
    } catch (Exception $e) {
        $newId = null;
    }
    
    $processed['users'][$user['id']] = $newId;
    return $newId;
}

/**
 * 
 * @param array|int $group
 * @param array $processed
 * @param Zend_Db_Adapter_Abstract $oldDb
 * @return int|null
 */
function processGroup($group, &$processed, $oldDb)
{
    if (empty($group)) {
        return null;
    }
    
    if (!is_array($group)) {
        if (array_key_exists($group, $processed['groups'])) {
            return $processed['groups'][$group];
        }
        $select = $oldDb->select()->from('ugroup')->where('id = ?', $group);
        $stmt = $oldDb->query($select);
        $row = $stmt->fetch();
        if (!empty($row)) {
            $group = $row;
        } else {
            return null;
        }
    }
    
    if (array_key_exists($group['id'], $processed['groups'])) {
        return $processed['groups'][$group['id']];
    }
    
    $data = array(
        'name' =>  html_entity_decode($group['name']),
        'shortname' => (null === $group['abbr']) ? '' : $group['abbr']
    );
    
    $groupModel = new Default_Model_Ugroup();
    $groupModel->setData($data);
    try {
        $groupModel->save();
        $newId = $groupModel['ugroup_id'];
    } catch (Exception $e) {
        $newId = null;
    }
    
    $processed['groups'][$group['id']] = $newId;
    return $newId;
}

/**
 * 
 * @param int $oldId
 * @param int $newId
 * @param array $processed
 * @param Zend_Db_Adapter_Abstract $oldDb
 * @return string|null
 */
function copyActions($oldId, $newId, &$processed, $oldDb)
{
    $select = $oldDb->select()->from('action')->where('ticket_id = ?', $oldId)->order('date');
    $stmt = $oldDb->query($select);
    $result = $stmt->fetchAll();
    if (!empty($result)) {
        foreach ($result as $row) {
            $data = array(
                'create_date' => $row['date'],
                'comment' => html_entity_decode($row['description']),
                'ticket_id' => $newId
            );
            
            $data['user_id'] = processUser($row['user'], $processed, $oldDb);
            
            $changeset = new Default_Model_Changeset();
            $changeset->setData($data);
            try {
                $changeset->save();
            } catch (Exception $e) {}
        }
        
        return $row['date'];
    }
    
    return null;
}

/**
 * 
 * @param int $oldId
 * @param int $newId
 * @param Zend_Db_Adapter_Abstract $oldDb
 */
function copyUploads($oldId, $newId, $oldDb)
{
    $select = $oldDb->select()->from('upload')->where('ticket_id = ?', $oldId);
    $stmt = $oldDb->query($select);
    $result = $stmt->fetchAll();
    if (!empty($result)) {
        foreach ($result as $row) {
            $data = array(
                'name' => $row['name'],
                'mimetype' => $row['type'],
                'content_length' => $row['size'],
                'content' => $row['content'],
                'ticket_id' => $newId
            );
            
            $upload = new Default_Model_Upload();
            $upload->setData($data);
            try {
                $upload->save();
            } catch (Exception $e) {}
        }
    }
}