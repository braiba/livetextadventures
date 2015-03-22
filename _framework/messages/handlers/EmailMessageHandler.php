<?php

class EmailMessageHandler extends MessageHandler {

	protected $email;

	public function __construct($filter,$email){
		parent::__construct($filter);
		$this->email = $email;
	}

	public function msg($msg,$msg_level){
		if ( $msg_level & (Messages::M_BACKGROUND_ERROR|Messages::M_SYSTEM_STATUS_ERROR) ){
			$subject = '[Error] '.TextUtils::neatTruncate($msg, 64);
		} elseif ( $msg_level & (Messages::M_BACKGROUND_WARNING|Messages::M_SYSTEM_STATUS_WARNING) ){
			$subject = '[Warning] '.TextUtils::neatTruncate($msg, 64);
		} else {
			$subject = TextUtils::neatTruncate($msg, 72);
		}
		$envelope = array(
			'to' => $this->email,
			'from' => 'Xbite Ltd <info@xbitegames.co.uk>',
			'subject' => $subject,
			'date' => date('r')
		);
		$body = $this->buildEmailBody($msg,$msg_level);
		$smtpMail = new SMTPMail(SMTPMail::XBITE_GAMES);
		@$smtpMail->sendMessage(imap_mail_compose($envelope, $body));
	}

	protected function buildEmailBody($msg,$msg_level){
		$html = '<h2 class="center">ERROR REPORT</h2>';

		$table = new CalculationTable();
		$table->setFootSize(0);
		$table->addRow('Time',date('h:i:sa j/n/Y'));
		$table->addRow('Message',$msg);
		$url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
		$table->addRow('Page', HTMLUtils::a($url,get_page_title()));
		if (function_exists('getCurrentUserName')){
			$table->addRow('User',getCurrentUserName());
		}
		$html.= $table->display();

		// *** Debug info ***
		$full_info_error_codes = (Messages::M_SYSTEM_STATUS_ERROR | Messages::M_BACKGROUND_ERROR | Messages::M_CODE_ERROR | Messages::M_ERROR  | Messages::M_PHP_ERROR | Messages::M_SQL_ERROR | Messages::M_DEBUG);
		if ($msg_level & $full_info_error_codes ){
			if (!empty($_GET)){
				$html.= '<h3 class="center">GET Values</h3>';
				$table = new CalculationTable(array_keys($_GET),array_values($_GET));
				$table->setFootsize(0);
				$html.= $table->display();
			}

			if (!empty($_POST)){
				$html.= '<h3 class="center">POST Values</h3>';
				$table = new CalculationTable(array_keys($_POST),array_values($_POST));
				$table->setFootsize(0);
				$html.= $table->display();
			}

			$html.= '<h3 class="center">Stack Trace</h3>';
			$columns = array(
				array(
					'label'=>'Location',
					'callback'=>function($row,$col){
						if (!isset($row['file'])){
							return '[anonymous function]';
						}
						if (defined('SVN_FILE_ROOT')){
							$file = $row['file'];
							$svn_page = str_replace('\\','/',str_replace('D:\\WebRoot\\',SVN_FILE_ROOT,$file)).'#L'.$row['line'];
							return HTMLUtils::a($svn_page,basename($file).' ('.$row['line'].')');
						}
						return 'line '.$row['line'].' in '.$row['file'];
					}
				),
				array(
					'label'=>'Call',
					'callback'=>function($row,$col){
						$func = ( isset($row['class']) ? $row['class'].$row['type'] : '' ).$row['function'];
						$readable_args = array_map(array('HTMLUtils','debug_displayvar'),$row['args']);
						return $func.'('.implode(', ',$readable_args).')';
					}
				)
			);
			$stack_trace = debug_backtrace();
			foreach ($stack_trace as $i=>$data){
				if (isset($data['class']) && $data['class']==__CLASS__){
					unset($stack_trace[$i]);
				} else {
					break;
				}
			}
			$table = new ArrayTablePlus($stack_trace,$columns);
			$html.= $table->display();
		}

		$css = file_get_contents('./css/email.css');
		$html = HTMLUtils::classesToStyles($html,$css);

		return array(
			//header
			array(
				'type' => TYPEMULTIPART,
				'type.parameters' => array(
					'BOUNDARY' => 'XBL_' . uniqid()
				),
				'subtype' => 'ALTERNATIVE'
			),
			// plain version
			array(
				'type' => TYPETEXT,
				'subtype' => 'PLAIN',
				'encoding' => ENCBASE64,
				'charset' => 'WINDOWS-1252',
				'contents.data' => imap_binary(HTMLUtils::toPlainText($html))
			),
			// html version
			array(
				'type' => TYPETEXT,
				'subtype' => 'HTML',
				'encoding' => ENCBASE64,
				'charset' => 'WINDOWS-1252',
				'contents.data' => imap_binary($html)
			)
		);

	}

}

?>