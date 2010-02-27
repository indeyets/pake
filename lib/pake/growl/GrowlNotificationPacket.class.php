<?php

class GrowlNotificationPacket extends GrowlPacket
{
    const PACKET_TYPE = 1;

    private $m_szData;

    public function __construct($szApplication = "growlnotify", $szNotification =  "Command-Line Growl Notification", $szTitle = "Title", $szDescription = "Description", $nPriority = 0, $bSticky = False, $szPassword = "" )
    {
        $nFlags = ($nPriority & 7) * 2;
        if ($nPriority < 0)
            $nFlags |= 8;
        if ($bSticky)
            $nFlags |= 256;

        $this->m_szData = pack(
            "c2n5",
            self::PROTOCOL_VERSION, self::PACKET_TYPE, $nFlags,
            strlen($szNotification), strlen($szTitle), strlen($szDescription), strlen($szApplication)
        );

        $this->m_szData .= $szNotification;
        $this->m_szData .= $szTitle;
        $this->m_szData .= $szDescription;
        $this->m_szData .= $szApplication;

        $this->m_szData .= self::getChecksum($this->m_szData, $szPassword);
    }

    public function payload()
    {
        return $this->m_szData;
    }
}
