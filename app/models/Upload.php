<?php

class Default_Model_Upload extends Default_Model_Abstract
{
    protected static $_resourceNameInit = 'Default_Model_Db_Upload';
	
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
     * @return Default_Model_Upload
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
     * @return Default_Model_Upload
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
     * @return Default_Model_Db_Upload
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }
    
    public static function getIdFromNameAndTicket($name, $ticketId)
    {
    	$select = self::getResourceInstance()->select()->where('name = ?', $name)->where('ticket_id = ?', $ticketId);
    	if ($upload = self::fetchRow($select)) {
    		return $upload->getId();
    	}
    	
    	return null;
    }
    
    public static function getUniqueName($name, $ticketId, $skipOrig = false)
    {
        $resource = self::getResourceInstance();
        
        if (!$skipOrig) {
            $select = $resource->select()
                ->from('upload', array('upload_id'))
                ->where('name = ?', $name)
                ->where('ticket_id = ?', $ticketId);
            if (self::fetchRow($select) === null) {
                return $name;
            }
        }
        
        if ($pos = strrpos($name, '.')) {
            $filename = substr($name, 0, $pos);
            $fileext  = strrchr($name, '.');
        } else {
            $filename = $name;
            $fileext  = '';
        }
        
        $i = 2;
        do {
            $select = $resource->select()
                ->from('upload', array('upload_id'))
                ->where('name = ?', "{$filename}({$i}){$fileext}")
                ->where('ticket_id = ?', $ticketId);
            if (self::fetchRow($select) === null) {
                return "{$filename}({$i}){$fileext}";
            }
             
            $i++;
        } while (true);
    }
    
    public static function detectMimeType($value, $acceptHeader = true)
    {
        if (file_exists($value['name'])) {
            $file = $value['name'];
        } else if (file_exists($value['tmp_name'])) {
            $file = $value['tmp_name'];
        } else {
            return null;
        }
        
        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            if (!empty($value['options']['magicFile'])) {
                $mime = new finfo($const, $value['options']['magicFile']);
            } else {
                $mime = new finfo($const);
            }

            if ($mime !== false) {
                $result = $mime->file($file);
            }

            unset($mime);
        }

        if (empty($result) && (function_exists('mime_content_type')
            && ini_get('mime_magic.magicfile'))) {
            $result = mime_content_type($file);
        }

        if (empty($result)) {
            $fileext = substr(strrchr($value['name'], '.'), 1);
            switch ($fileext) {
                case 'jpg' :
                case 'jpeg' :
                case 'jpe' :
                    $result = 'image/jpeg';
                    break;
                case 'png' :
                case 'gif' :
                case 'bmp' :
                case 'tiff' :
                    $result = 'image/'.strtolower($fileext);
                    break;
                case 'css' :
                    $result = 'text/css';
                    break;
                case 'xml' :
                    $result = 'application/xml';
                    break;
                case 'doc' :
                case 'docx' :
                    $result = 'application/msword';
                    break;
                case 'xls' :
                case 'xlsx' :
                case 'xlt' :
                case 'xltx' :
                case 'xlm' :
                case 'xld' :
                case 'xla' :
                case 'xlc' :
                case 'xlw' :
                case 'xll' :
                    $result = 'application/vnd.ms-excel';
                    break;
                case 'ppt' :
                case 'pptx' :
                case 'pps' :
                case 'ppsx' :
                case 'pot' :
                case 'potx' :
                    $result = 'application/vnd.ms-powerpoint';
                    break;
                case 'rtf' :
                    $result = 'application/rtf';
                    break;
                case 'pdf' :
                    $result = 'application/pdf';
                    break;
                case 'html' :
                case 'shtml' :
                case 'htm' :
                    $result = 'text/html';
                    break;
                case 'txt' :
                    $result = 'text/plain';
                    break;
                case 'zip' :
                    $result = 'application/zip';
                    break;
            }
        }
        
        if (empty($result) && $acceptHeader) {
            $result = $value['type'];
        }
        
        if (empty($result)) {
            $result = 'application/octet-stream';
        }

        return $result;
    }
    
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
}