<?php

class GrowlPacket
{
    const PROTOCOL_VERSION = 1;

    private function __construct()
    {
    }

    public static function getChecksum($payload, $password = '')
    {
        return pack("H32", md5($payload.$password));
    }
}