<?php

/**
 * Class nMailer
 */
class nMailer {

	/** @var resource */
	protected $imapStream;

	/** @var ExceptionThrower */
	protected $exceptionThrower;

	/** @var string */
	protected $systemCharset;

	/** @var object */
	protected $spamChecker;

	/**
	 * nMailServer constructor.
	 */
	public function __construct() {
		$this->systemCharset    = ini_get('default_charset');
		$this->exceptionThrower = new ExceptionThrower();
	}

	/**
	 * @return bool
	 * @throws nMailException
	 */
	public function ping() {
		try {
			$this->exceptionThrower->start();
			$success = imap_ping($this->imapStream);
			$this->exceptionThrower->stop();

			return $success;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error ping '.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param int $id
	 *
	 * @return nMail
	 * @throws nMailException
	 */
	public function retrieveMail($id) {
		$mail = new nMail();

		$mail->setMailHeader($this->retrieveHeaders($id))
			 ->setRawBody($this->retrieveRawBody($id))
			 ->setBody($this->retrieveBody($id))
			 ->setAttachments($this->retrieveAttachments($id))
			 ->setSpamChecker($this->spamChecker);

		return $mail;
	}

	/**
	 * @return array
	 * @throws nMailException
	 */
	public function retrieveAllMails() {
		$mails = array();
		for ($i = 1; $i <= $this->countAllMails(); $i++) {
			$mails[] = $this->retrieveMail($i);
		}

		return $mails;
	}

	/**
	 * @param $id
	 *
	 * @return nMailHeaders|null
	 * @throws nMailException
	 */
	public function retrieveHeaders($id) {
		try {
			$this->exceptionThrower->start();
			$mail_header = imap_header($this->imapStream, $id);
			$raw         = imap_fetchheader($this->imapStream, $id);
			$this->exceptionThrower->stop();

			if ($mail_header !== null) {
				return new nMailHeaders($mail_header, $raw);
			}

			return null;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error retrieving header no: '.$id.' .'.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param $id
	 *
	 * @return string
	 * @throws nMailException
	 */
	public function retrieveRawBody($id) {
		try {
			$this->exceptionThrower->start();
			$raw = imap_body($this->imapStream, $id);
			$this->exceptionThrower->stop();

			return $raw;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error retrieving header no: '.$id.' .'.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param int $id
	 *
	 * @throws nMailException
	 * @return string
	 */
	public function retrieveBody($id) {
		try {
			$this->exceptionThrower->start();

			$charset = '';
			$structure = imap_fetchstructure($this->imapStream, $id);

			if ($structure->ifparameters) {
				foreach ($structure->parameters as $param) {
					if ($param->attribute == 'charset') {
						$charset = $param->value;
					}
				}
			}
			if (empty($charset) && !empty($structure->parts[0]->parameters[0]->attribute) && $structure->parts[0]->parameters[0]->attribute == 'charset') {
				$charset = $structure->parts[0]->parameters[0]->value;
			}

			if (empty($charset) || $charset == 'us-ascii') {
				$charset = 'utf-8';
			}

			$body = $this->get_part($this->imapStream, $id, 'TEXT/HTML', $structure);
			if (!$body) {
				$body = $this->get_part($this->imapStream, $id, 'TEXT/PLAIN', $structure);
				$body = nl2br(htmlentities($body, ENT_QUOTES, $charset));
			}

			$this->exceptionThrower->stop();
			return ($this->systemCharset == $charset ? $body : mb_convert_encoding($body, $this->systemCharset, $charset));
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error retrieving body no: '.$id.' .'.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 * @throws nMailException
	 */
	public function markAsRead($id) {
		try {
			$this->exceptionThrower->start();
			$result = imap_setflag_full($this->imapStream, $id, "\\Seen");
			imap_expunge($this->imapStream);
			$this->exceptionThrower->stop();

			return $result;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error getting the number of unread mails'.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $id email identifier
	 *
	 * @throws nMailException
	 * @return array
	 */
	public function retrieveAttachments($id) {
		try {
			$attachments = array();
			$this->exceptionThrower->start();
			$structure = imap_fetchstructure($this->imapStream, $id);
			$this->exceptionThrower->stop();

			if (!empty($structure->parts)) {
				foreach ($structure->parts as $key => $value) {
					$encoding    = $structure->parts[$key]->encoding;

					$param_merge = array();

					if ($structure->parts[$key]->ifdparameters) {
						$param_merge = $structure->parts[$key]->dparameters;
					}

					if ($structure->parts[$key]->ifparameters) {
						$param_merge = array_merge($param_merge, $structure->parts[$key]->parameters);
					}

					if ($param_merge) {
						foreach ($param_merge as $param) {
							if (in_array(strtolower($param->attribute), array('filename', 'name'))) {
								$name = iconv_mime_decode($param->value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR);
								$name = trim(preg_replace('/=\?.*\?=/s', '' , $name));
								break;
							}
						}
						if (empty($name)) {
//							$name = 'NotFoundName.err';
							continue;
						}

						$this->exceptionThrower->start();
						$message = imap_fetchbody($this->imapStream, $id, $key + 1);
						if ($encoding == 0 || $encoding == 1) {
							$message = imap_8bit($message);
						}
						if ($encoding == 2) {
							$message = imap_binary($message);
						}
						if ($encoding == 3) {
							$message = imap_base64($message);
						}
						if ($encoding == 4) {
							$message = imap_qprint($message);
						}
						$this->exceptionThrower->stop();
						$name_ext = pathinfo($name);
						$attachments[] = new nMailAttachment($name_ext['filename'], $message, $name_ext['extension'], strlen($message));
					}
				}
			}

			return $attachments;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error retrieving attachments no: '.$id.' .'.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @throws nMailException
	 * @return int|null
	 */
	public function countUnreadMails() {
		try {
			$this->exceptionThrower->start();
			$info = imap_mailboxmsginfo($this->imapStream);
			$this->exceptionThrower->stop();

			return (isset($info->Unread) ? (int)$info->Unread : null);
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error getting the number of unread mails: '.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param $string
	 *
	 * @return array
	 * @throws nMailException
	 * @internal param null $flags
	 */
	public function searchMails($string) {
		try {
			$this->exceptionThrower->start();
			$search = imap_search($this->imapStream, $string);
			$this->exceptionThrower->stop();

			return $search;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error in the search: '.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @throws nMailException
	 * @return int
	 */
	public function countAllMails() {
		try {
			$this->exceptionThrower->start();
			$count = imap_num_msg($this->imapStream);
			$this->exceptionThrower->stop();

			return $count;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error getting the number of mails: '.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $id email identifier
	 *
	 * @throws nMailException
	 * @return bool
	 */
	public function delete($id) {
		try {
			$this->exceptionThrower->start();
			$success = imap_delete($this->imapStream, $id);
			imap_expunge($this->imapStream);
			$this->exceptionThrower->stop();

			return $success;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error deleting the mails no '.$id.': '.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @throws nMailException
	 * @return bool
	 */
	public function close() {
		try {
			$this->exceptionThrower->start();
			$success = imap_close($this->imapStream, CL_EXPUNGE);
			$this->exceptionThrower->stop();

			return $success;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error closing the connection: '.$e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $url
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool
	 * @throws nMailException
	 */
	public function connect($url, $username, $password) {
		try {
			if ($this->imapStream) {
				$this->close();
			}

			$this->exceptionThrower->start();
			$this->imapStream = imap_open($url, $username, $password);
			$this->exceptionThrower->stop();

			return (bool)$this->imapStream;
		} catch (Exception $e) {
			$this->exceptionThrower->stop();
			throw new nMailException('Error open the connection: '.$e->getMessage()."; debug data: url='$url', username='$username', password='$password'", $e->getCode());
		}
	}

	/**
	 * @param $structure
	 *
	 * @return string
	 */
	protected function extractMimeType($structure) {
		$primary_mime_type = array(
			'TEXT',
			'MULTIPART',
			'MESSAGE',
			'APPLICATION',
			'AUDIO',
			'IMAGE',
			'VIDEO',
			'MODEL',
			'OTHER'
		);

		if ($structure->subtype) {
			return $primary_mime_type[(int)$structure->type].(!empty($structure->subtype) ? '/'.$structure->subtype : '');
		}

		return 'TEXT/PLAIN';
	}

	/**
	 * @param resource $stream
	 * @param int      $msg_number
	 * @param string   $mime_type
	 * @param Object   $structure
	 * @param string   $part_number
	 *
	 * @return bool|string
	 */
	protected function get_part($stream, $msg_number, $mime_type, $structure = null, $part_number = '') { // Get Part Of Message Internal Private Use
		if (!$structure) {
			$structure = imap_fetchstructure($stream, $msg_number);
			if (!$structure) {
				return false;
			}
		}

		if ($mime_type == $this->extractMimeType($structure)) {
			$text = imap_fetchbody($stream, $msg_number, ($part_number ? $part_number : '1'));
			if ($structure->encoding == 3) {
				return imap_base64($text);
			}
			if ($structure->encoding == 4) {
				return imap_qprint($text);
			}

			return $text;
		}

		if ($structure->type == TYPEMULTIPART && !empty($structure->parts)) {
			foreach ($structure->parts as $index => $sub_structure) {
				$data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure, ($part_number ? $part_number.'.' : '').($index + 1));
				if ($data) {
					return $data;
				}
			}
		}

		return false;
	}

	/**
	 * @param string $systemCharset
	 *
	 * @return nMailer
	 */
	public function setSystemCharset($systemCharset) {
		$this->systemCharset = $systemCharset;

		return $this;
	}

	/**
	 * @param object $spamChecker
	 *
	 * @return nMailer
	 */
	public function setSpamChecker($spamChecker) {
		$this->spamChecker = $spamChecker;

		return $this;
	}

	/**
	 * @param $email
	 *
	 * @return bool|string
	 */
	public static function filterEmail($email) {
		$start = strpos($email, '<');
		$end   = strrpos($email, '>');

		return ($start === false || $end === false ? $email : substr($email, $start + 1, $end - $start - 1));
	}
}
