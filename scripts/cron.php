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
$conf = $bootstrap->getOption('cron');

$bootstrap->bootstrap('db');
$bootstrap->bootstrap('autoload');
$bootstrap->bootstrap('registry');
$bootstrap->bootstrap('theme');
$bootstrap->bootstrap('routes');
$front = $bootstrap->getResource('frontController');
$front->getRouter()->addDefaultRoutes();
$front->setBaseUrl((!empty($conf['baseurl']) ? $conf['baseurl'] : null));

//TODO: Add more logic here to ensure that this cron doesn't run more often than it should

/* @var $view Zend_View */
$view = $bootstrap->getResource('view');
$status = Default_Model_Attribute::get('status');
$priority = Default_Model_Attribute::get('priority');

$resource = Default_Model_Ticket::getResourceInstance();
$select = Default_Model_Ticket::getSelectFromSearch(array(
        $status['attribute_id'] => array(
            'mode' => '!',
            'value' => array('closed', 'on hold')
        ),
        $priority['attribute_id'] => 'critical'
    ), null, false, true);

$rowset = $resource->fetchAll($select);
if (!$rowset->count()) {
    exit();
}

$server = $conf['servername'];

$staticAttrs = Default_Model_Ticket::getStaticAttrs();
$attrs = Default_Model_Attribute::getAll();
foreach ($rowset as $row) {
    $dates  = array(
        'created' => $row['created'],
        'modified' => $row['modified']
    );

    $target = new Zend_Date();
    $target->subHour(3);

    if ($target->compare(new Zend_Date($dates['modified'], Zend_Date::ISO_8601)) < 0) {
        continue;
    }

    $ticket = $row;
    $latest = Default_Model_AttributeValue::getLatestByTicketId($ticket['ticket_id']);

    $notification = new Zend_Mail('UTF-8');
    $notification->setSubject('REMINDER: #' . $ticket['ticket_id'] . ': ' . $ticket['summary']);
    $notification->setFrom(Default_Model_Setting::get('notification_from'));
    $replyTo = Default_Model_Setting::get('notification_replyto');
    if (!empty($replyTo)) {
        $notification->setReplyTo($replyTo);
    }
    $recipients = Default_Model_Ticket::getReminderRecipients($latest);
    foreach ($recipients as $to) {
        $notification->addTo($to[0], $to[1]);
    }

    $view->clearVars();
    $view->ticket = $ticket;
    $view->latest = $latest;
    $view->dates = $dates;
    $view->staticAttrs = $staticAttrs;
    $view->attrs = $attrs;
    unset($view->attrs['description']);

    $colWidth = 0;
    foreach ($view->staticAttrs as $col) {
        if (strlen($col['label']) > $colWidth) {
            $colWidth = strlen($col['label']);
        }
    }
    foreach (array_keys($view->dates) as $col) {
        if (strlen($col) > $colWidth) {
            $colWidth = strlen($col);
        }
    }
    foreach ($view->attrs as $col) {
        if (strlen($col['label']) > $colWidth) {
            $colWidth = strlen($col['label']);
        }
    }
    $view->colWidth = $colWidth;

    $view->server = $server;

    $body = $view->render('ticket/reminder.phtml');
    $notification->setBodyText($body);

    $notification->send();
}