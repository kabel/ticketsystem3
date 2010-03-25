<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected $_version = '0.1.1';
    
    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Default_',
            'basePath'  => dirname(__FILE__),
        ));
        return $autoloader;
    }
    
    protected function _initUpdates()
    {
        $this->bootstrap('db');
        $this->bootstrap('autoload');
        
        $version = Default_Model_Version::findRow('core');
        
        if ($version && (version_compare($version->getVersion(), $this->_version) === -1)) {
            $fromVersion = $version->getVersion();
            $toVersion = $this->_version;
            
            $sqlFilesDir = dirname(__FILE__) . '/sql';
            if (!is_dir($sqlFilesDir) || !is_readable($sqlFilesDir)) {
                return;
            }
            
            $arrAvailableFiles = array();
            $sqlDir = dir($sqlFilesDir);
            while (false !== ($sqlFile = $sqlDir->read())) {
                $matches = array();
                if (preg_match('#^update-(.*)\.php$#i', $sqlFile, $matches)) {
                    $arrAvailableFiles[$matches[1]] = $sqlFile;
                }
            }
            $sqlDir->close();
            if (empty($arrAvailableFiles)) {
                return;
            }
            
            $arrModifyFiles = $this->_getUpdateSqlFiles($fromVersion, $toVersion, $arrAvailableFiles);
            if (empty($arrModifyFiles)) {
                return;
            }
            
            foreach ($arrModifyFiles as $resourceFile) {
                $sqlFile = $sqlFilesDir.'/'.$resourceFile['fileName'];
                $fileType = pathinfo($resourceFile['fileName'], PATHINFO_EXTENSION);
                
                try {
                    if ($fileType == 'php') {
                        $result = include($sqlFile);
                    } else {
                        $result = false;
                    }
                    
                    if ($result) {
                        $version->setVersion($resourceFile['toVersion']);
                        $version->save();
                    }
                } catch (Exception $e) {
                    error_log(print_r($e,1));
                }
            }
        }
    }
    
    protected function _getUpdateSqlFiles($fromVersion, $toVersion, $arrFiles)
    {
        $arrRes = array();
        
        uksort($arrFiles, 'version_compare');
        foreach ($arrFiles as $version => $file) {
            $version_info = explode('-', $version);

            // In array must be 2 elements: 0 => version from, 1 => version to
            if (count($version_info)!=2) {
                break;
            }
            $infoFrom = $version_info[0];
            $infoTo   = $version_info[1];
            if (version_compare($infoFrom, $fromVersion) !== -1 && version_compare($infoTo, $toVersion) !== 1) {
                $arrRes[] = array('toVersion'=>$infoTo, 'fileName'=>$file);
            }
        }
        
        return $arrRes;
    }
    
    protected function _initRegistry()
    {
        $registry = Zend_Registry::getInstance();
        Zend_Registry::set('bootstrap', $this);
        
        return $registry;
    }
    
    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('XHTML1_STRICT');
        $view->env = $this->getEnvironment();
    }
    
    protected function _initRoutes()
    {
        $this->bootstrap('frontController');
        /* @var $front Zend_Controller_Front */
        $front = $this->frontController;
        $routes = new Zend_Config($this->getOption('routes'));
        $router = $front->getRouter();
        $router->addConfig($routes);
    }
    
    protected function _initTheme()
    {
        $basePath = dirname(__FILE__);
        $theme = $this->getTheme();
        
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->setBasePath($basePath . '/design/' . $theme . '/templates');
        $view->addHelperPath('TicketSystem/View/Helper', 'TicketSystem_View_Helper');
        
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $layout->setLayoutPath($basePath . '/design/' . $theme . '/layouts');
        //$layout->setView = $view;
    }
    
    protected function _initAcl()
    {
        $this->bootstrap('db');
        $acl = new Zend_Acl();
        
        //ACL Resources
        $acl->addResource('config');
        $acl->addResource('ticket');
        
        //ACL Roles
        $acl->addRole((string)Default_Model_User::LEVEL_GUEST);
        $acl->addRole((string)Default_Model_User::LEVEL_USER, (string)Default_Model_User::LEVEL_GUEST);
        $acl->addRole((string)Default_Model_User::LEVEL_MODERATOR, (string)Default_Model_User::LEVEL_USER);
        $acl->addRole((string)Default_Model_User::LEVEL_ADMIN);
        
        //ACL Rules
        $acl->allow((string)Default_Model_User::LEVEL_ADMIN);
        $acl->allow((string)Default_Model_User::LEVEL_GUEST, 'config', 'profile');
        if (!Default_Model_Setting::get('restrict_guest')) {
            $acl->allow((string)Default_Model_User::LEVEL_GUEST, 'ticket', 'create');
        } else {
            $acl->allow((string)Default_Model_User::LEVEL_USER, 'ticket', 'create');
        }
        
        if (!Default_Model_Setting::get('restrict_view_user')) {
            $acl->allow((string)Default_Model_User::LEVEL_GUEST, 'ticket', 'view-all');
        } else {
            if (Default_Model_Setting::get('allow_view_group')) {
                $acl->allow((string)Default_Model_User::LEVEL_GUEST, 'ticket', 'view-group');
            } else {
                $acl->allow((string)Default_Model_User::LEVEL_MODERATOR, 'ticket', 'view-group');
            }
        }
        
        $acl->allow((string)Default_Model_User::LEVEL_MODERATOR, 'ticket', 'edit-cc');
        $acl->allow((string)Default_Model_User::LEVEL_MODERATOR, 'ticket', 'reassign');
        
        return $acl;
    }
    
    public function getTheme()
    {
        $skinConfig = $this->getOption('skin');
        $theme = is_null($skinConfig['theme']) ? 'default' : $skinConfig['theme'];
        
        return $theme;
    }
}

