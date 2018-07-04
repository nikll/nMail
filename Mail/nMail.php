<?php

/**
 * Class nMail
 */
class nMail {

	/** @var nMailHeaders */
	private $mailHeader;

	/** @var string */
	private $rawBody;

	/** @var string */
	private $body;

	/** @var nMailAttachment[] */
	private $attachments = array();

	/** @var int */
	private $spamScore = 0;

	/** @var object $spamChecker*/
	private $spamChecker;

	/**
	 * @param object $spamChecker
	 *
	 * @return nMail
	 */
	public function setSpamChecker($spamChecker) {
		$this->spamChecker = $spamChecker;

		return $this;
	}

	/**
	 * @return nMailHeaders
	 */
	public function getMailHeader() {
		return $this->mailHeader;
	}

	/**
	 * @param nMailHeaders $mailHeader The header
	 *
	 * @return $this
	 */
	public function setMailHeader($mailHeader) {
		$this->mailHeader = $mailHeader;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @param string $body The body
	 *
	 * @return $this
	 */
	public function setBody($body) {
		$this->body = $body;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRawBody() {
		return $this->rawBody;
	}

	/**
	 * @param string $rawBody
	 *
	 * @return $this
	 */
	public function setRawBody($rawBody) {
		$this->rawBody = $rawBody;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getSpamScore() {
		if ($this->spamChecker && !$this->spamScore) {
			$this->setSpamScore($this->spamChecker->check($this->getMailHeader()->getFrom(), $this->getMailHeader()->getRaw().$this->getRawBody()));
		}

		return $this->spamScore;
	}

	/**
	 * @param int $spamScore
	 */
	public function setSpamScore($spamScore) {
		$this->spamScore = $spamScore;
	}

	/**
	 * @return array
	 */
	public function getAttachments() {
		return $this->attachments;
	}

	/**
	 * @param array $attachments The attachments
	 *
	 * @return $this
	 */
	public function setAttachments(array $attachments) {
		$this->attachments = $attachments;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isAttachments() {
		return !empty($this->attachments);
	}

	/**
	 * @param string $dir
	 * @param string $prefixName
	 *
	 * @return array
	 */
	public function saveAttachments($dir, $prefixName = '') {
		$files = array();

		foreach ($this->attachments as $file) {
			/** @var nMailAttachment $file */
			$name = $prefixName.'/'.$file->__toString();
			$file->save($dir.'/'.$name);
			$files[] = $name;
		}

		return $files;
	}

	/**
	 * @param string $pattern The pattern to search
	 *
	 * @return boolean
	 */
	public function search($pattern) {
		// Search in the body and in the headers
		if (is_int(strpos($this->body, $pattern)) || $this->mailHeader->search($pattern)) {
			return true;
		}

		// Search in the attachment
		foreach ($this->attachments as $attach) {
			if ($attach->searchFilename($pattern) || $attach->searchContent($pattern)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return array(
			'header'     => $this->mailHeader,
			'body'       => $this->body,
			'attachment' => $this->attachments
		);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$string = '';
		$string .= $this->mailHeader;
		$string .= "\n--------------------------------------------------------------------------------\n";
		$string .= $this->body;
		$string .= "\n--------------------------------------------------------------------------------\n";
		$string .= "Attached files: \n";
		foreach ($this->attachments as $part) {
			$string .= "\t * ".$part."\n";
		}

		return $string;
	}
}
