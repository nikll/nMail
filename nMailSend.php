<?php

/**
 * Class nMailSend
 * отправка емейлов через smtp сервера в соовтествии с rfc в кодировке utf-8
 * TODO: запилить вложени€ файлов
 */
class nMailSend {

	/* @var null|resource */
	private $connect;

	/* @var int */
	private $connectErrno;

	/* @var string */
	private $connectErrstr;

	/* @var bool */
	private $debug = false;

	/* @var int */
	private $errNo;

	/* @var array */
	private static $smtpErrCodes = array(
		421 => 'Service not available, closing channel',
		432 => 'A password transition is needed',
		450 => 'Requested mail action not taken: mailbox unavailable',
		451 => 'Requested action aborted: error in processing',
		452 => 'Requested action not taken: insufficient system storage',
		454 => 'Temporary authentication failure',
		500 => 'Syntax error, command not recognized',
		501 => 'Syntax error in parameters or arguments',
		502 => 'Command not implemented',
		503 => 'Bad sequence of commands',
		504 => 'Command parameter not implemented',
		530 => 'Authentication required',
		534 => 'Authentication mechanism is too weak',
		535 => 'Authentication failed',
		538 => 'Encryption required for requested authentication mechanism',
		550 => 'Requested action not taken: mailbox unavailable',
		551 => 'User not local, please try forwarding',
		552 => 'Requested mail action aborted: exceeding storage allocation',
		553 => 'Requested action not taken: mailbox name not allowed',
		554 => 'Transaction failed'
	);

	/* @var string */
	private $serverResponse;

	/* @var array|string */
	private $sender = '';

	/* @var string */
	private $server = 'localhost';

	/* @var bool */
	private $login = false;

	/* @var bool */
	private $pass = false;

	/* @var string */
	private $authMethod = 'cram-md5';

	/* @var string */
	private $mimeType = 'text/html';

	/**
	 * @var string
	 */
	private $charset = 'windows-1251'; //'utf-8';

	/**
	 * @param array $default_params
	 */
	public function __construct(array $default_params) {
		if ($default_params) {
			$this->apply_params($default_params);
		}
	}

	/**
	 * @param array $params
	 */
	private function apply_params($params) {
		foreach (array('sender', 'server', 'login', 'pass', 'mimeType', 'authMethod', 'debug', 'charset') as $key) {
			if (isset($params[$key])) {
				$this->$key = $params[$key];
			}
		}
	}

	/**
	 * @param $emails
	 *
	 * @return array
	 */
	public static function parseEmails($emails) {
		$result = array();
		foreach (explode(',', $emails) as $email) {
			$start = strpos($email, '<');
			$end   = strrpos($email, '>');
			if ($start !== false && $end !== false) {
				$name = substr($email, 0, $start);
				$mail = substr($email, $start + 1, $end - $start - 1);
				$result[$mail] = $name;
			} else {
				$result[$email] = $email;
			}
		}
		return $result;
	}

	/**
	 * @param $str
	 *
	 * @return string
	 */
	private function mimeHeader($str) {
		return ($str ? '=?'.$this->charset.'?B?'.base64_encode($str).'?=' : '');
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	private function prep(array $data) {
		$return = array();
		foreach ($data as $mail => $name) {
			$return[] = $this->mimeHeader($name).' <'.$mail.'>';
		}

		return implode(', ', $return);
	}

	/** отправка мыла напр€мую через tcp подключение на порт smtp сервера
	 *
	 * @param string     $mess       сообщение
	 * @param array      $recipients получатели
	 * @param string     $subject    тема
	 * @param array|null $params     доп параметры экземл€ра класса
	 *
	 * @return bool|bool[]           true если все в пор€дке и письмо успешно отправленно либо false если гдето ошибка, в случае ошибки информацию можно получить через метод get_last_error()
	 */
	public function send($mess, array $recipients, $subject = 'ѕисьмо', array $params = array()) {
		if ($params) {
			$this->apply_params($params);
		}

		$domain = substr(key($this->sender), strpos(key($this->sender), '@') + 1);

		$mess = implode("\n", array(
			'From: '.$this->prep($this->sender),
			'To: '.$this->prep($recipients),
			'Subject: '.$this->mimeHeader($subject),
			'MIME-Version: 1.0',
			'User-Agent: phpMailer',
			'Date: '.date('r'),
			'X-Priority: 3',
			'X-MSMail-Priority: Normal',
			'X-Mailer: WebDev php mailer',
			'X-Powered-By: nikll',
			'X-Descriptions: '.$_SERVER['HTTP_HOST'].' Powered by nikll',
			'Content-Type: '.$this->mimeType.'; charset="'.$this->charset.'"',
			'Content-Transfer-Encoding: base64',
			'',
			chunk_split(base64_encode($mess)).'.'
		));

		$return = array();

		if (!$this->connect = fsockopen($this->server, 25, $this->connectErrno, $this->connectErrstr)) {
			$this->log("Can't open SMTP connect. \n".$this->connectErrstr.' ('.$this->connectErrno.')');

			return false;
		}

		if (!$this->step()) {
			return false;
		}
		if (!$this->step('EHLO '.$domain) && $this->errNo >= 500 && !$this->step('HELO '.$domain)) {
			return false;
		}

		if ($this->login && $this->pass) {
			switch ($this->authMethod) {
				case 'cram-md5':
					if ($this->step('AUTH CRAM-MD5') && $this->step(base64_encode($this->login.' '.hash_hmac('md5', base64_decode($this->serverResponse), $this->pass)))) {
						break;
					}
				case 'plain':
					if ($this->step('AUTH PLAIN '.base64_encode($this->login.chr(0).$this->login.chr(0).$this->pass))) {
						break;
					}
				case 'login':
				default:
					if (!$this->step('AUTH LOGIN') || !$this->step(base64_encode($this->login)) || !$this->step(base64_encode($this->pass))) {
						return false;
					}
			}
		}

		if (!$this->step('MAIL FROM: <'.key($this->sender).'>')) {
			return false;
		}

		foreach ($recipients as $mail => $name) {
			if ($this->step('RCPT TO: <'.$mail.'>')) {
				$return[$mail] = true;
			} elseif ($this->errNo == 550 || $this->errNo == 552) {
				$return[$mail] = false;
			} else {
				return false;
			}
		}

		foreach (array('DATA', $mess, 'RSET'."\n".'QUIT') as $send) {
			if (!$this->step($send)) {
				return false;
			}
		}

		fclose($this->connect);

		return $return;
	}

	/** возвращает информацию о послдней ошибке
	 * @return array код и расшифровка ошибки
	 */
	public function get_last_error() {
		return array(
			'errNo'   => $this->errNo,
			'message' => (isset(self::$smtpErrCodes[$this->errNo]) ? self::$smtpErrCodes[$this->errNo] : 'Unknown response, error code: '.$this->errNo).';  Server: '.$this->serverResponse
		);
	}

	/** метод дл€ отправки сообщени€ серверу и проверки ответа
	 *
	 * @param null|string $send —ообщение серверу (если не указывать то просто прочитает ответ сервера)
	 *
	 * @return bool
	 */
	private function step($send = null) {
		if ($this->debug) {
			echo 'send: '.$send."\n";
			$this->log('send: '.$send);
		}

		if ($send !== null) {
			fwrite($this->connect, $this->crlf($send));
		}
		$line = fread($this->connect, 1024);

		$this->errNo          = (int)substr($line, 0, 3);
		$this->serverResponse = substr($line, 4);

		if ($this->debug) {
			echo $line."\n";
			$this->log($line);
		}

		return $this->errNo < 400;
	}

	/** пишет в лог
	 *
	 * @param string $text строка дл€ записи в лог
	 *
	 * @return int
	 */
	private function log($text) {
		return file_put_contents(MAIN_SITE_PATH.'logs/crm.mail'.date('Y-m-d').'.log', $text."\n", FILE_APPEND);
	}

	/** преобразует окончани€ строк к виду CRLF и заканчивает строку если она не закончена
	 *
	 * @param string $s
	 *
	 * @return mixed
	 */
	public function crlf($s) {
		return str_replace("\n", "\r\n", $this->lf($s));
	}

	/** преобразует окончани€ строк к виду LF и заканчивает строку если она не закончена
	 *
	 * @param string $s
	 *
	 * @return mixed
	 */
	public function lf($s) {
		if ($s{strlen($s) - 1} !== "\n") {
			$s .= "\n";
		}

		return str_replace(array("\r\n", "\r", "\n\r"), "\n", $s);
	}
}
