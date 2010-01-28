<?php

	require_once(dirname(__FILE__) . '/lib/class.email.php');

	Class extension_SMTP_Email_Library extends Extension{
		
		const HTACCESS_PEAR_INCLUDE = "## EMAIL EXTENSION PEAR LIBRARY\nphp_value include_path  \"extensions/smtp_email_library/lib/pear:.\"";
		
		public function about(){
			return array('name' => 'SMTP Email Library',
						 'version' => '1.0',
						 'release-date' => '2010-01-28',
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://www.pointybeard.com',
										   'email' => 'alistair@symphony-cms.com')
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
					
				array(
					'page' => '/blueprints/events/new/',
					'delegate' => 'AppendEventFilter',
					'callback' => 'cbAddFilterToEventEditor'
				),

				array(
					'page' => '/blueprints/events/edit/',
					'delegate' => 'AppendEventFilter',
					'callback' => 'cbAddFilterToEventEditor'
				),	
				
				array(
					'page' => '/blueprints/events/new/',
					'delegate' => 'AppendEventFilterDocumentation',
					'callback' => 'cbAppendEventFilterDocumentation'
				),

				array(
					'page' => '/blueprints/events/edit/',
					'delegate' => 'AppendEventFilterDocumentation',
					'callback' => 'cbAppendEventFilterDocumentation'
				),				
					
				array(
					'page' => '/frontend/',
					'delegate' => 'EventPostSaveFilter',
					'callback' => 'cbSendEmailSMTPFilter'
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
					'/### Symphony 2\.0\.x ###\n*/i', 
					"### Symphony 2.0.x ###\n\n" . self::HTACCESS_PEAR_INCLUDE . "\n\n", 
					$htaccess
				);
			}
			else{
				//clean up the extra new line characters
				$htaccess = preg_replace(
					'/### Symphony 2\.0\.x ###\n*/i', 
					"### Symphony 2.0.x ###\n", 
					$htaccess
				);				
			}
			
			return @file_put_contents(DOCROOT . '/.htaccess', $htaccess);
		}

		private function __sendEmailFindFormValue($needle, $haystack, $discard_field_name=true, $default=NULL, $collapse=true){

			if(preg_match('/^(fields\[[^\]]+\],?)+$/i', $needle)){
				$parts = preg_split('/\,/i', $needle, -1, PREG_SPLIT_NO_EMPTY);
				$parts = array_map('trim', $parts);

				$stack = array();
				foreach($parts as $p){ 
					$field = str_replace(array('fields[', ']'), '', $p);
					($discard_field_name ? $stack[] = $haystack[$field] : $stack[$field] = $haystack[$field]);
				}

				if(is_array($stack) && !empty($stack)) return ($collapse ? implode(' ', $stack) : $stack);
				else $needle = NULL;
			}

			$needle = trim($needle);
			if(empty($needle)) return $default;

			return $needle;

		}
		
		public function cbAppendEventFilterDocumentation(array $context=array()){
			if(!in_array('smtp-email-library-send-email-filter', $context['selected'])) return;
			
			$context['documentation'][] = new XMLElement('h3', __('Send Email via Direct SMTP Connection'));

			$context['documentation'][] = new XMLElement('p', __('The send email filter, upon the event successfully saving the entry, takes input from the form and send an email to the desired recipient. <b>This filter currently does not work with the "Allow Multiple" option.</b> The following are the recognised fields:'));

			$context['documentation'][] = contentBlueprintsEvents::processDocumentationCode(
				'send-email[sender-email] // '.__('Optional').self::CRLF.
				'send-email[sender-name] // '.__('Optional').self::CRLF.						
				'send-email[subject] // '.__('Optional').self::CRLF.
				'send-email[body]'.self::CRLF.
				'send-email[recipient] // '.__('comma separated list of author usernames.'));

			$context['documentation'][] = new XMLElement('p', __('All of these fields can be set dynamically using the exact field name of another field in the form as shown below in the example form:'));

	        $context['documentation'][] = contentBlueprintsEvents::processDocumentationCode('<form action="" method="post">
	<fieldset>
		<label>'.__('Name').' <input type="text" name="fields[author]" value="" /></label>
		<label>'.__('Email').' <input type="text" name="fields[email]" value="" /></label>
		<label>'.__('Message').' <textarea name="fields[message]" rows="5" cols="21"></textarea></label>
		<input name="send-email[sender-email]" value="fields[email]" type="hidden" />
		<input name="send-email[sender-name]" value="fields[author]" type="hidden" />		
		<input name="send-email[subject]" value="You are being contacted" type="hidden" />
		<input name="send-email[body]" value="fields[message]" type="hidden" />
		<input name="send-email[recipient]" value="fred" type="hidden" />
		<input id="submit" type="submit" name="action[save-contact-form]" value="Send" />
	</fieldset>
</form>');
		}
		
		public function cbSendEmailSMTPFilter(array $context=array()){
			
			if(!in_array('smtp-email-library-send-email-filter', $context['event']->eParamFILTERS)) return;

			$fields = $_POST['send-email'];
			
			$fields['recipient'] = $this->__sendEmailFindFormValue($fields['recipient'], $_POST['fields'], true);
			$fields['recipient'] = preg_split('/\,/i', $fields['recipient'], -1, PREG_SPLIT_NO_EMPTY);
			$fields['recipient'] = array_map('trim', $fields['recipient']);

			$fields['recipient'] = Symphony::Database()->fetch("SELECT `email`, CONCAT(`first_name`, ' ', `last_name`) AS `name` FROM `tbl_authors` WHERE `username` IN ('".@implode("', '", $fields['recipient'])."') ");

			$fields['subject'] = $this->__sendEmailFindFormValue($fields['subject'], $context['fields'], true, __('[Symphony] A new entry was created on %s', array(Symphony::Configuration()->get('sitename', 'general'))));
			$fields['body'] = $this->__sendEmailFindFormValue($fields['body'], $context['fields'], false, NULL, false);
			$fields['sender-email'] = $this->__sendEmailFindFormValue($fields['sender-email'], $context['fields'], true, 'noreply@' . parse_url(URL, PHP_URL_HOST));
			$fields['sender-name'] = $this->__sendEmailFindFormValue($fields['sender-name'], $context['fields'], true, 'Symphony');
			$fields['from'] = $this->__sendEmailFindFormValue($fields['from'], $context['fields'], true, $fields['sender-email']);		
						
			$section = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `id` = ".$context['event']->getSource()." LIMIT 1");
			
			$edit_link = URL.'/symphony/publish/'.$section['handle'].'/edit/'.$context['entry_id'].'/';

			$body = __('Dear <!-- RECIPIENT NAME -->,') . General::CRLF . General::CRLF . __('This is a courtesy email to notify you that an entry was created on the %1$s section. You can edit the entry by going to: %2$s', array($section['name'], $edit_link)). General::CRLF . General::CRLF;

			if(is_array($fields['body'])){
				foreach($fields['body'] as $field_handle => $value){
					$body .= "=== $field_handle ===" . General::CRLF . General::CRLF . $value . General::CRLF . General::CRLF;
				}
			}

			else $body .= $fields['body'];

			$errors = array();

			if(!is_array($fields['recipient']) || empty($fields['recipient'])){
				$context['messages'][] = array('smtp-email-library-send-email-filter', false, __('No valid recipients found. Check send-email[recipient] field.'));
			}

			else{
				
				foreach($fields['recipient'] as $r){
					
					$email = new LibraryEmail;

					$email->to = vsprintf('%2$s <%1$s>', array_values($r));
					$email->from = sprintf('%s <%s>', $fields['sender-name'], $fields['sender-email']);
					$email->subject = $fields['subject'];
					$email->message = str_replace('<!-- RECIPIENT NAME -->', $r['name'], $body);
					$email->setHeader('Reply-To', $fields['from']);

					try{
						$email->send();
					}
					catch(Exception $e){
						$errors[] = $email;
					}

				}

				if(!empty($errors)){
					$context['messages'][] = array('smtp-email-library-send-email-filter', false, 'The following email addresses were problematic: ' . General::sanitize(implode(', ', $errors)));
				}

				else $context['messages'][] = array('smtp-email-library-send-email-filter', true);
			}
		}

		public function cbAddFilterToEventEditor($context){
			$context['options'][] = array(
				'smtp-email-library-send-email-filter', @in_array('smtp-email-library-send-email-filter', $context['selected']), 'Send Email via Direct SMTP Connection'
			);			
			
		}
	
	}

