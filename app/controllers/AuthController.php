<?php

class AuthController extends TicketSystem_Controller_EmptyAction
{
    public function indexAction()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            return $this->_forward('index', 'index');
        }
        
        $session = new Zend_Session_Namespace('TicketSystem');
        
        $controller = $this->getRequest()->getParam('controller');
        if ($controller === 'auth') {
            $session->returnUrl = $this->view->url(array('action' => 'index', 'controller' => 'index'), 'default', true);
        } else {
            $session->returnUrl = $this->view->url();
        }
        
        $this->_loadLoginForms();
        $this->render('form');
    }
    
    public function loginAction()
    {
        if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
		$form = new Default_Form_Login();
		if (!$form->isValid($_POST)) {
			$this->_loadLoginForms($form);
			return $this->render('form');
		}
		
		//Init Resource to ensure it's ready
		Default_Model_User::getResourceInstance();
		
		$values = $form->getValues();
		$result = $this->_authenticate($values['username'], $values['passwd'], $userInfo);
		$auth = Zend_Auth::getInstance();
		
		if ($result->isValid()) {
		    if ($userInfo->status == Default_Model_User::STATUS_BANNED) {
		        $auth->clearIdentity();
		        $this->view->messages = $this->_getBannedMessage();
		        $this->_loadLoginForms($form);
			    return $this->render('form');
		    }
			$auth->getStorage()->write($userInfo);
			$session = new Zend_Session_Namespace('TicketSystem');
			$returnUrl = $session->returnUrl;
			unset($session->returnUrl);
			
			return $this->_helper->redirector->gotoUrl($returnUrl, array('prependBase' => false));
		} else {
			$this->view->messages = array(
				'type' => 'error',
				'content' => array('Invalid username or password')
			);
			$this->_loadLoginForms($form);
			
			return $this->render('form');
		}
    }
    
    public function casAction()
    {
        $auth = $this->_getCASAdapter();
        if (!$auth->isLoggedIn()) {
            $auth->login();
        } else {
            $user = $auth->getUser();
            $select = Default_Model_User::getResourceInstance()->select()
                ->where('login_type = ?', Default_Model_User::LOGIN_TYPE_CAS)
                ->where('username = ?', $user);
            $userModel = Default_Model_User::fetchRow($select);
            
            if (!empty($userModel)) {
                if ($userModel->getStatus() == Default_Model_User::STATUS_BANNED) {
                    $this->view->messages = $this->_getBannedMessage(true);
                    $this->_loadLoginForms();
                    return $this->render('form');
                }
                
                $tempArray = $userModel->toArray();
                unset($tempArray['passwd']);
                Zend_Auth::getInstance()->getStorage()->write((object)$tempArray);
                
                if (empty($userModel['info']) || empty($userModel['email'])) {
                    return $this->_helper->redirector('profile', 'config', null, array(
                    	'view' => 'oldCAS'
                    ));
                }
            } else {
                $pf = new UNL_Peoplefinder();
                /* @var $pf UNL_Peoplefinder_Driver_WebService */
                $pfResult = $pf->getUID($user);
                $info = (!empty($pfResult->eduPersonNickname)) ? $pfResult->eduPersonNickname :  $pfResult->displayName;
                $email = '';
                if (isset($pfResult->mail)) {
                    if (isset($pfResult->unlEmailAlias)) {
                        $email = $pfResult->unlEmailAlias . '@unl.edu';
                    } else {
                        $email = $pfResult->mail;
                    }
                }
                
                $userModel->setData(array(
                    'username' => $user,
                    'passwd' => '',
                    'login_type' => Default_Model_User::LOGIN_TYPE_CAS,
                    'level' => Default_Model_User::LEVEL_USER,
                    'status' => Default_Model_User::STATUS_ACTIVE,
                    'info' => $info,
                    'email' => $email
                ));
                 
                $userModel->save();
                
                $tempArray = $userModel->toArray();
                unset($tempArray['passwd']);
                Zend_Auth::getInstance()->getStorage()->write((object)$tempArray);
                
                if (empty($userModel['email'])) {
                    return $this->_helper->redirector('profile', 'config', null, array(
                        'view' => 'newCAS'
                    ));
                }
            }
            
            $session = new Zend_Session_Namespace('TicketSystem');
            $returnUrl = $session->returnUrl;
            unset($session->returnUrl);
			return $this->_helper->redirector->gotoUrl($returnUrl, array('prependBase' => false));
        }
    }
    
    public function logoutAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) { 
            $userInfo = $auth->getIdentity();
            $auth->clearIdentity();
            session_unset();
            Zend_Session::regenerateId();
            $this->view->messages = array(
    			'type' => 'success',
    			'content' => array('You successfully logged out')
    		);
    		if ($userInfo->login_type == Default_Model_User::LOGIN_TYPE_CAS) {
    		    $this->view->messages['content'][] = $this->_getCASLogoutMessage();
    		}
        }
		
		return $this->_forward('index', 'auth');
    }
    
    public function logoutCasAction()
    {
        $auth = $this->_getCASAdapter();
        
        if (isset($_SERVER['HTTPS'])
            && !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] == 'on') {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }
        
        return $auth->logout($protocol . '://' . $_SERVER['SERVER_NAME'] . $this->view->url(array(
            'action' => 'index',
            'controller' => 'index'
        ), 'default', true));
    }
    
    private function _authenticate($username, $passwd, &$userInfo, $useOldHash = false)
    {
        $db = Zend_Registry::get('bootstrap')->getResource('db');
		
        $hash = ($useOldHash) ? 'OLD_PASSWORD(?)' : 'MD5(?)';
        $typeFilter = ' AND login_type = ' . (int) Default_Model_User::LOGIN_TYPE_LEGACY;
        
		$authAdapter = new Zend_Auth_Adapter_DbTable($db, 'user', 'username', 'passwd', $hash . $typeFilter);
		$authAdapter->setIdentity($username)
		            ->setCredential($passwd);
					
		$result = Zend_Auth::getInstance()->authenticate($authAdapter);
		
		if ($useOldHash) {
		    if ($result->isValid()) {
		        $userInfo = $authAdapter->getResultRowObject(null, 'passwd');
		        $user = Default_Model_User::findRow($userInfo->user_id);
		        $user['passwd'] = md5($passwd);
		        $user->save();
		    }
		    
		    return $result;
		}
		
		if ($result->isValid()) {
		    $userInfo = $authAdapter->getResultRowObject(null, 'passwd');
		    return $result;
		} elseif (Default_Model_Setting::get('allow_old_password')) {
		    return $this->_authenticate($username, $passwd, $userInfo, true);
		} else {
		    return $result;
		}
    }
    
    private function _getCASAdapter()
    {
        $auth = UNL_Auth::factory('SimpleCAS', array('requestClass' => 'Zend_Http_Client'));
        
        return $auth;
    }
    
    private function _getBannedMessage($isCAS=false)
    {
        $messages = array(
            'type' => 'notice',
            'content' => array('Your account has been blocked from this service')
        );
        if ($isCAS) {
            $messages['content'][] = $this->_getCASLogoutMessage();
        }
        
        return $messages;
    }
    
    private function _getCASLogoutMessage()
    {
        return 'If finished with all services, please <a href="' . $this->view->url(array('action' => 'logout-cas', 'controller' => 'auth'), 'default', true) . '">logout</a> from CAS';
    }
    
    private function _loadLoginForms($legacy=null, $cas=null)
    {
        if (is_null($legacy)) {
            $legacy = new Default_Form_Login();
        }
        
        if (is_null($cas)) {
            $cas = new Default_Form_LoginCAS();
        }
        
        $this->view->form_legacy = $legacy;
        $this->view->form_cas = $cas;
    }
}