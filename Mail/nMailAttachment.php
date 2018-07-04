<?php

/**
 * Class nMailAttachment
 */
class nMailAttachment {

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $extension;

    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    private $size;

    /**
     * @param string $name      The name
     * @param string $content   The content
     * @param string $extension The extension
     * @param int    $size      The size
     */
    public function __construct($name, $content, $extension = '', $size = 0) {
        $this->name      = $name;
        $this->content   = $content;
        $this->extension = $extension;
        $this->size      = $size;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name The name of the file
     *
     * @return nMailAttachment
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * @param string $extension The extension
     *
     * @return nMailAttachment
     */
    public function setExtension($extension) {
        $this->extension = $extension;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

	/**
	 * @param $fileName
	 *
	 * @return bool|int
	 */
	public function save($fileName) {
    	return file_put_contents($fileName, $this->getContent());
	}

    /**
     * @param string $content The content
     *
     * @return nMailAttachment
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @param string $size The size
     *
     * @return nMailAttachment
     */
    public function setSize($size) {
        $this->size = $size;

        return $this;
    }

    /**
     * @param string $pattern The pattern to search
     *
     * @return boolean
     */
    public function searchFilename($pattern) {
        return is_int(strpos($this->__toString(), $pattern));
    }

    /**
     * @param string $pattern The pattern to search
     *
     * @return boolean
     */
    public function searchContent($pattern) {
        return is_int(strpos($this->content, $pattern));
    }

    /**
     * @return array
     */
    public function toArray() {
        return array(
            'name'      => $this->name,
            'extension' => $this->extension,
            'content'   => $this->content,
            'size'      => $this->size
        );
    }

    /**
     * @return string   filename.extension
     */
    public function __toString() {
        return $this->name.'.'.$this->extension;
    }
}
