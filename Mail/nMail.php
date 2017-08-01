<?php

class nMail {

    /**
     * @var nMailHeaders
     */
    private $mailHeader;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $attachments;

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
