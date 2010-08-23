<?php

	Class PEARSMTP_Email extends Email{
	
		public function __construct(){
			require_once "Mail.php";
			parent::__construct();
		}
	
		public function send(){
		
			$this->validate();

			$headers = array(
				'Message-ID'	=> sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['SERVER_NAME']),
				'Return-Path'	=> $this->sender_email_address,
				'Importance'	=> 'normal',
				'Priority'		=> 'normal',
				'X-Sender'		=> 'Symphony Email Module <noreply@symphony-cms.com>',
				'X-Mailer'		=> 'Symphony Email Module',
				'X-Priority'	=> '3',
				'MIME-Version'	=> '1.0',
				'Content-Type'	=> 'text/plain; charset=UTF-8',
				'To'		 	=> $this->recipient,
				'From'			=> "{$this->sender_name} <{$this->sender_email_address}>",
		 		'Reply-To'		=> $this->sender_email_address,
				'Subject' 		=> $this->subject
			);
		
			foreach($this->headers as $name => $value){
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
		
			$mail = $smtp->send(
				$this->recipient, 
				$headers, 
				$this->message
			);

			if(PEAR::isError($mail)){
				throw new Exception($mail->getMessage());
			}
		
			return true;
		}	
	}
	
	return 'PEARSMTP_Email';