<?php

/**
 * Does not use namespace
 * Class ApacheNote
 */
class ApacheNote
{
    /**
     * @var ApacheNote
     */
    private static $apacheNote;

    private $note = [];

    protected function __construct() {}

    /**
     * @return ApacheNote
     */
    public static function getInstance()
    {
        return (self::$apacheNote) ? self::$apacheNote : self::$apacheNote = new ApacheNote();
    }

    /**
     * @param $note_name
     * @return bool
     */
    public function getNote($note_name)
    {
        return isset($this->note[$note_name]) ? $this->note[$note_name] : false;
    }

    /**
     * @param $note_name
     * @param $note_value
     * @return bool
     */
    public function setNote($note_name, $note_value)
    {
        //returns current value when set new value
        $response = $this->getNote($note_name);

        $this->note = [$note_name => $note_value];

        return $response;
    }

    /**
     * @throws \RuntimeException
     */
    public final function __clone() {
        throw new \RuntimeException('Clone is not allowed against '. get_class($this));
    }

}

?>
