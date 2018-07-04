<?

/**
 * Class nMailSpamChecker
 */
class nMailSpamChecker {

	/** @var array */
	protected $emails_not_spam;

	/**
	 * spamChecker constructor.
	 *
	 * @param array $emails_not_spam
	 */
	public function __construct(array $emails_not_spam = array()) {
		$this->emails_not_spam = $emails_not_spam;
	}

	/**
	 * @uses http://spamcheck.postmarkapp.com/
	 *
	 * @param string $from
	 * @param string $rawMail
	 * @param bool   $verbose
	 *
	 * @return int|array
	 * @throws Exceptions
	 */
	public function check($from, $rawMail, $verbose = false) {
		if (isset($emails_not_spam[$from]) || isset($emails_not_spam[nMailer::filterEmail($from)])) {
			return 0;
		}

		$curl_options = array(
			CURLOPT_URL            => 'http://spamcheck.postmarkapp.com/filter',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_HTTPHEADER     => array(
				'Accept: application/json',
				'Content-Type: application/json',
			),
			CURLOPT_POSTFIELDS     => json_encode(array(
				'email'   => iconv('cp1251', 'utf-8', $rawMail),
				'options' => ($verbose ? 'long' : 'short'),
			))
		);

		$ch = curl_init();
		curl_setopt_array($ch, $curl_options);
		$result = curl_exec($ch);
		$error  = curl_error($ch);
		$code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if (!$result) {
			throw new Exceptions('cURL Error: '.$error.'; http code: '.$code);
		}

		$result = json_decode($result, true);

		if (empty($result['success'])) {
			throw new Exceptions('Spam check postmark Error: '.(!empty($result['message']) ? $result['message'] : 'unknown'));
		}

		return ($verbose ? $result : (float)$result['score']);
	}
}