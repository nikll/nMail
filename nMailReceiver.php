<?php

class nMailReceiver {
    /** @var nMailer */
    protected $mailer;

    /** @var string */
    protected $username = '';

    /** @var string */
    protected $password = '';

    /** @var string */
    protected $host = '';

    /** @var string */
    protected $protocol = 'imap';

    /** @var integer */
    protected $port = 143;

    /** @var bool */
    protected $ssl = false;

    /** @var string */
    protected $folder = 'INBOX';


    /**
     * @param string $host
     * @param string $username
     * @param string $password
     *
     * @throws nMailException
     */
    public function __construct($host, $username, $password) {
        if (!in_array('imap', get_loaded_extensions(), true)) {
            throw new nMailException(
                'It looks like you do not have imap installed.'."\n".
                'IMAP is required to make request to the mail servers. For install instructions, visit the following page: http://php.net/manual/en/imap.installation.php',
                E_USER_WARNING
            );
        }

        $this->host     = $host;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    protected function getUrl() {
        return '{'.$this->host.':'.$this->port.'/'.$this->protocol.($this->ssl ? '/ssl' : '/notls').'/novalidate-cert}'.$this->folder;
    }

    /**
     * @return nMailer
     * @throws nMailException
     */
    public function createMailer() {
        $this->mailer = new nMailer();

        $this->mailer->connect($this->getUrl(), $this->username, $this->password);

        return $this->mailer;
    }

    /**
     * @param string $protocol
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setProtocol($protocol) {
        if (!in_array($protocol, array('imap', 'pop3', 'nntp'), true)) {
            throw new InvalidArgumentException("$protocol is not valid protocol");
        }

        $this->protocol = $protocol;

        return $this;
    }

    /**
     * @return string
     */
    public function getProtocol() {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username) {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * @return bool
     */
    public function isSsl() {
        return $this->ssl;
    }

    /**
     * @param bool $ssl
     */
    public function setSsl($ssl) {
        $this->ssl = $ssl;
    }

    /**
     * @return string
     */
    public function getFolder() {
        return $this->folder;
    }

    /**
     * @param string $folder
     */
    public function setFolder($folder) {
        $this->folder = $folder;
    }

}
