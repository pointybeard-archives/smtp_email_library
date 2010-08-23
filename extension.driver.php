<?php

	Class extension_SMTP_Email_Library extends Extension{
		
		const HTACCESS_PEAR_INCLUDE = "## EMAIL EXTENSION PEAR LIBRARY\nphp_value include_path  \"extensions/smtp_email_library/lib/pear:.\"";
		
		public function about(){
			return array('name' => 'SMTP Email Library',
						 'version' => '2.0',
						 'release-date' => '2010-08-23',
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://alistairkearney.com',
										   'email' => 'hi@alistairkearney.com')
				 		);
		}
		
		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'cbAppendPreferences'
				),
			
				array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => 'cbSavePreferences'
				),
					
			);
		}
				
		public function uninstall(){
			self::__update_htaccess(true);
			Symphony::Configuration()->remove('smtp_email_library');
			$this->_Parent->saveConfig();
		}
			
		public function cbAppendPreferences($context){

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('SMTP Email Library')));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Host'));
			$label->appendChild(Widget::Input('settings[smtp_email_library][host]', Symphony::Configuration()->get('host', 'smtp_email_library')));
			$div->appendChild($label);

			$label = Widget::Label(__('Port'));
			$label->appendChild(Widget::Input('settings[smtp_email_library][port]', Symphony::Configuration()->get('port', 'smtp_email_library')));
			$div->appendChild($label);
			$group->appendChild($div);

			
			$label = Widget::Label();
			$input = Widget::Input('settings[smtp_email_library][auth]', '1', 'checkbox');
			if(Symphony::Configuration()->get('auth', 'smtp_email_library') == '1') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Requires authentication');
			$group->appendChild($label);
						
			$group->appendChild(new XMLElement('p', 'Some SMTP connections require authentication. If that is the case, enter the username/password combination below.', array('class' => 'help')));		

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Username'));
			$label->appendChild(Widget::Input('settings[smtp_email_library][username]', Symphony::Configuration()->get('username', 'smtp_email_library')));
			$div->appendChild($label);

			$label = Widget::Label(__('Password'));
			$label->appendChild(Widget::Input('settings[smtp_email_library][password]', Symphony::Configuration()->get('password', 'smtp_email_library')));
			$div->appendChild($label);
			$group->appendChild($div);

			$context['wrapper']->appendChild($group);

		}
		
		public function cbSavePreferences($context){
			if(!isset($context['settings']['smtp_email_library']['auth'])) $context['settings']['smtp_email_library']['auth'] = '0';
		}
		
		public function enable(){
			return self::__update_htaccess();
		}

		public function disable(){
			return self::__update_htaccess(true);
		}

		public function install(){
			return self::__update_htaccess();
		}

		private static function __update_htaccess($removing=false){
			
			$htaccess = @file_get_contents(DOCROOT . '/.htaccess');

			if($htaccess === false) return false;

			## Remove existing rules
			$htaccess = str_replace(self::HTACCESS_PEAR_INCLUDE, NULL, $htaccess);	
			
			if($removing == false){
				$htaccess = preg_replace(
					'/### Symphony 2(\.\d)?\.x ###\n*/i', 
					"### Symphony 2.x ###\n\n" . self::HTACCESS_PEAR_INCLUDE . "\n\n", 
					$htaccess
				);
			}
			else{
				//clean up the extra new line characters
				$htaccess = preg_replace(
					'/### Symphony 2(\.\d)?\.x ###\n*/i', 
					"### Symphony 2.x ###\n", 
					$htaccess
				);
			}
			
			return @file_put_contents(DOCROOT . '/.htaccess', $htaccess);
		}
	}

