<?php
/**
 * Package which connects to the peoplefinder services to get information about UNL people.
 *
 * PHP version 5
 * 
 * @category  Services 
 * @package   UNL_Services_Peoplefinder
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2007 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://pear.unl.edu/
 */

/**
 * This is the basic class for utilizing the UNL Peoplefinder service which can
 * give you various pieces of information about a given uid (uniue user id).
 * 
 * @package UNL_Services_Peoplefinder
 */
class UNL_Services_Peoplefinder
{
    static $_vCardCache = array();
    static $_hCardCache = array();
    
    /**
     * returns the name for a given uid
     *
     * @param string $uid Unique id for the user to get info about.
     * 
     * @return string|false
     */
    static function getFullName($uid)
    {
        if ($vcard = UNL_Services_Peoplefinder::getVCard($uid)) {
            $matches = array();
            preg_match_all('/FN:(.*)/', $vcard, $matches);
            if (isset($matches[1][0]) && $matches[1][0] != ' ') {
                return $matches[1][0];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * returns the email address for the given uid
     *
     * @param string $uid Unique id for the user to get info about.
     * 
     * @return string|false
     */
    static function getEmail($uid)
    {
        if ($hcard = UNL_Services_Peoplefinder::getHCard($uid)) {
            $matches = array();
            preg_match_all('/mailto:([^\'\"]*)/', $hcard, $matches);
            if (isset($matches[1][0])) {
                return $matches[1][0];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Gets an hcard for the uid given.
     *
     * @param string $uid Unique id for the user to get info about.
     * 
     * @return string|false
     */
    static function getHCard($uid)
    {
        if (!isset(self::$_hCardCache[$uid])) {
            self::$_hCardCache[$uid] = @file_get_contents('http://peoplefinder.unl.edu/hcards/'.$uid);
        }
        
        return self::$_hCardCache[$uid];
    }
    
    /**
     * Gets a vcard for the given uid.
     *
     * @param string $uid Unique id for the user to get info about.
     * 
     * @return string|false
     */
    static function getVCard($uid)
    {
        if (!isset(self::$_vCardCache[$uid])) {
            self::$_vCardCache[$uid] = @file_get_contents('http://peoplefinder.unl.edu/vcards/'.$uid);
        }
        
        return self::$_vCardCache[$uid];
    }
}

?>