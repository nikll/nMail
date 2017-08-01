<?php

class nMailHeaders {

    /**
     * @var int
     */
    private $msgNo;

    /**
     * @var string
     */
    private $to;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $reply;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var bool
     */
    private $unseen;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var int
     */
    private $size;

    /**
     * @param Object $mail_header
     */
    public function __construct($mail_header) {
//      stdClass Object (
//          [date] => Mon, 31 Jul 2017 11:37:27 +0300
//          [Date] => Mon, 31 Jul 2017 11:37:27 +0300
//          [subject] => =?koi8-r?B?9MXT1CAxMjM=?=
//          [Subject] => =?koi8-r?B?9MXT1CAxMjM=?=
//          [message_id] => <1501490247.328139.4906.33185@mail.rambler.ru>
//          [toaddress] => nikll@ur66.ru
//          [to] => Array (
//              [0] => stdClass Object (
//                  [mailbox] => nikll
//                  [host] => ur66.ru
//              )
//          )
//          [fromaddress] => =?koi8-r?B?68/UzNHSz9cg7snLz8zByg==?= <nikll@rambler.ru>
//          [from] => Array (
//              [0] => stdClass Object (
//                  [personal] => =?koi8-r?B?68/UzNHSz9cg7snLz8zByg==?=
//                  [mailbox] => nikll
//                  [host] => rambler.ru
//              )
//          )
//          [reply_toaddress] => =?koi8-r?B?68/UzNHSz9cg7snLz8zByg==?= <nikll@rambler.ru>
//          [reply_to] => Array (
//              [0] => stdClass Object (
//                  [personal] => =?koi8-r?B?68/UzNHSz9cg7snLz8zByg==?=
//                  [mailbox] => nikll
//                  [host] => rambler.ru
//              )
//          )
//          [senderaddress] => =?koi8-r?B?68/UzNHSz9cg7snLz8zByg==?= <nikll@rambler.ru>
//          [sender] => Array (
//              [0] => stdClass Object (
//                  [personal] => =?koi8-r?B?68/UzNHSz9cg7snLz8zByg==?=
//                  [mailbox] => nikll
//                  [host] => rambler.ru
//              )
//          )
//          [Recent] => N
//          [Unseen] =>
//          [Flagged] =>
//          [Answered] =>
//          [Deleted] =>
//          [Draft] =>
//          [Msgno] =>    1
//          [MailDate] => 31-Jul-2017 11:37:27 +0300
//          [Size] => 13908
//          [udate] => 1501490247
//      )

        $header_mail = array(
            'msgno'   => (property_exists($mail_header, 'msgno')           ? iconv_mime_decode($mail_header->msgno          ): null),
            'to'      => (property_exists($mail_header, 'toaddress')       ? iconv_mime_decode($mail_header->toaddress      ): null),
            'from'    => (property_exists($mail_header, 'fromaddress')     ? iconv_mime_decode($mail_header->fromaddress    ): null),
            'reply'   => (property_exists($mail_header, 'reply_toaddress') ? iconv_mime_decode($mail_header->reply_toaddress): null),
            'subject' => (property_exists($mail_header, 'subject')         ? iconv_mime_decode($mail_header->subject        ): null),
            'udate'   => (property_exists($mail_header, 'udate')           ? $mail_header->udate           : null),
            'unseen'  => (property_exists($mail_header, 'Unseen')          ? $mail_header->Unseen          : null),
            'size'    => (property_exists($mail_header, 'Size')            ? $mail_header->Size            : null)
        );

        $this->setMsgNo($header_mail['msgno']);
        $this->setTo($header_mail['to']);
        $this->setFrom($header_mail['from']);
        $this->setReply($header_mail['reply']);
        $this->subject = $header_mail['subject'];
        $this->setUnseen($header_mail['unseen'] == 'U');
        $this->date = new DateTime('@'.$header_mail['udate']);
        $this->size = $header_mail['size'];
    }

    /*********************/
    /* Getter and Setter */
    /*********************/

    /**
     * @param int $mso
     *
     * @return $this
     */
    public function setMsgNo($mso) {
        $this->msgNo = $mso;

        return $this;
    }

    /**
     * @return int
     */
    public function getMsgNo() {
        return $this->msgNo;
    }

    /**
     * Get To
     *
     * @return string
     */
    public function getTo() {
        return $this->to;
    }

    /**
     * Set the To
     *
     * @param string $to The To
     *
     * @return nMailHeaders
     */
    public function setTo($to) {
        $this->to = $to;

        return $this;
    }

    /**
     * Get the FROM
     *
     * @return string MailAddress
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * Set the FROM
     *
     * @param string|array $from The from
     *
     * @return nMailHeaders
     */
    public function setFrom($from) {
        $this->from = $from;

        return $this;
    }

    /**
     * Get the REPLAY
     *
     * @return string|array
     */
    public function getReply() {
        return $this->reply;
    }

    /**
     * Set the REPLAY
     *
     * @param array $reply The reply
     *
     * @return nMailHeaders
     */
    public function setReply($reply) {
        $this->reply = $reply;

        return $this;
    }

    /**
     * Get the SUBJECT
     *
     * @return string
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * Set the SUBJECT
     *
     * @param string $subject The subject
     *
     * @return nMailHeaders
     */
    public function setSubject($subject) {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get if it is UNREAD
     *
     * @return boolean
     */
    public function getUnseen() {
        return $this->unseen;
    }

    /**
     * Set if it is UNREAD
     *
     * @param string $unseen The unseen parameter
     *
     * @return nMailHeaders
     */
    public function setUnseen($unseen) {
        $this->unseen = $unseen;

        return $this;
    }

    /**
     * Get the DATE
     *
     * @return \DateTime
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set the DATE
     *
     * @param DateTime $date The date
     *
     * @return nMailHeaders
     */
    public function setDate(DateTime $date) {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the SIZE
     *
     * @return int
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * Set the SIZE
     *
     * @param int $size The size
     *
     * @return nMailHeaders
     */
    public function setSize($size) {
        $this->size = $size;

        return $this;
    }

    /**
     * Search in the header throught a pattern
     *
     * @param string $pattern The patter to search
     *
     * @return boolean
     */
    public function search($pattern) {
        return is_int(strpos($this->__toString(), $pattern));
    }

    /**
     * Convert the object to an array
     *
     * @return array
     */
    public function toArray() {
        return array(
            'msgno'   => $this->msgNo,
            'to'      => $this->to,
            'from'    => $this->from,
            'reply'   => $this->reply,
            'subject' => $this->subject,
            'unseen'  => $this->unseen,
            'date'    => $this->date,
            'size'    => $this->size
        );
    }

    /**
     * Convert the object to a string
     *
     * @return string
     */
    public function __toString() {
        $string = '';
        $string .= "To: $this->to";
        $string .= "\nFrom: $this->from";
        $string .= "\nReply: $this->reply";
        $string .= "\nSubject: $this->subject";
        $string .= "\nUnseen: $this->unseen";
        $string .= "\nDate: ".$this->date->format(DateTime::RFC1123);

        return $string;
    }
}
