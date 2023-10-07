<?php


namespace jsy\generator\parser\support;


class StringToStream
{
    /**
     * 代码内容
     *
     * @var array
     */
    static $content;

    /**
     * 在$content中的标示
     *
     * @var string
     */
    protected $key;

    /**
     * @var int
     */
    protected $pos;

    /**
     * @param $path
     * @param $mode
     * @param $options
     * @param $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, $opened_path) {

        $this->key = md5($path);
        if (! isset(self::$content[$this->key])) {
            self::$content[$this->key] = sprintf('<?php return %s;', substr($path, 11));
        }

        $this->pos = 0;
        return true;
    }

    /**
     * @param $count
     * @return string
     */
    public function stream_read($count) {
        $content = self::$content[$this->key];
        $ret     = substr($content, $this->pos, $count);
        $this->pos += strlen($ret);
        return $ret;
    }

    /**
     *
     */
    public function stream_stat() {

    }

    /**
     *
     */
    public function stream_eof() {

    }
}
