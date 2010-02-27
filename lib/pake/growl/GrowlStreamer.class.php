<?php

class GrowlStreamer
{
    private $fp;

    public function __construct($url = 'tcp://127.0.0.1:23052')
    {
        $this->fp = stream_socket_client($url, $errno, $errstr);

        if (!$this->fp) {
            throw new RuntimeException("Connection failed: (".$errno.") ".$errstr);
        }
    }

    public function __destruct()
    {
        fclose($this->fp);
    }

    public function send(GrowlPacket $packet)
    {
        fwrite($this->fp, $packet->payload());
    }
}
