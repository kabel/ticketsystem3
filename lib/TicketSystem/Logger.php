<?php

class TicketSystem_Logger
{
    /**
     * Cache for the logger object
     *
     * @var Zend_Log
     */
    protected static $_logger;

    /**
     * Log a message at a priority
     *
     * @param  string   $message   Message to log
     * @param  integer  $priority  Priority of message
     * @param  mixed    $extras    Extra information to log in event
     * @return void
     * @throws Zend_Log_Exception
     */
    public static function log($message, $priority, $extras = null)
    {
        if (null === self::$_logger) {
            self::$_logger = Zend_Registry::get('bootstrap')->getResource('log');
        }

        if (self::$_logger) {
            self::$_logger->log($message, $priority, $extras);
        }
    }
}