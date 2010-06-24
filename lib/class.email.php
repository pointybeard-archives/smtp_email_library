<?php

	Class LibraryEmail{
		
		private $_vars;
		
		public function __construct(){
			require_once "Mail.php";
			$this->_vars = array('headers' => array());
		}
		
		public function __set($name, $value){
			$this->_vars[$name] = $value;
		}
		
		public function setHeader($name, $value){
			$this->_vars['headers'][$name] = $value;
		}
		
		public function send(){
					
			$headers = array(
				'Message-ID'	=> sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['SERVER_NAME']),
				'Return-Path'	=> 'noreply@' . URL,
				'Importance'	=> 'normal',
				'Priority'		=> 'normal',
				'X-Sender'		=> 'Symphony Email Module <noreply@symphony-cms.com>',
				'X-Mailer'		=> 'Symphony Email Module',
				'X-Priority'	=> '3',
				'MIME-Version'	=> '1.0',
				'Content-Type'	=> 'text/plain; charset=UTF-8',
				'To'		 	=> $this->_vars['to'], 
				'From' 			=> $this->_vars['from'], 
			 	'Reply-To'		=> $this->_vars['from'],
				'Subject' 		=> $this->_vars['subject']
			);
			
			foreach($this->_vars['headers'] as $name => $value){
				$headers[$name] = $value;
			}

			$credentials = Symphony::Configuration()->get('smtp_email_library');
			
			$smtp = Mail::factory('smtp', array(
				'host' => $credentials['host'],
				'port' => $credentials['port'],
				'auth' => ((int)$credentials['auth'] == 1 ? true : false),
				'username' => $credentials['username'],
				'password' => $credentials['password'],
				'debug' => ((int)$credentials['debug'] == 1 ? true : false),
			));
			
			$smtp->send(
				$this->_vars['to'], 
				$headers, 
				$this->_vars['message']
			);
			
			if(PEAR::isError($mail)){
				throw new Exception($mail->getMessage());
			}
			
			return true;
		}		
		
	}
	
