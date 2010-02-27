<?php

class GrowlRegistrationPacket extends GrowlPacket
{
    const PACKET_TYPE = 0;

    private $m_szApplication;
    private $m_szPassword;

    private $m_aNotifications;
    private $m_szData;

    public function __construct($szApplication = "growlnotify", $szPassword = "")
    {
        $this->m_szApplication  = $szApplication;
        $this->m_szPassword     = $szPassword;
        $this->m_aNotifications = array();
    }

    public function addNotification($szNotification = "Command-Line Growl Notification", $bEnabled = True)
    {
        $this->m_aNotifications[$szNotification] = $bEnabled;
    }

    public function payload()
    {
        $szEncoded = $szDefaults = "";
        $nCount = $nDefaults = 0;

        foreach ($this->m_aNotifications as $szName => $bEnabled) {
            $szName = $szName;
            $szEncoded .= pack("n", strlen($szName)) . $szName;

            $nCount++;
            if ($bEnabled) {
                $szDefaults .= pack("c", $nCount - 1);
                $nDefaults++;
            }
        }

        $this->m_szData = pack("c2nc2", self::PROTOCOL_VERSION, self::PACKET_TYPE, strlen($this->m_szApplication), $nCount, $nDefaults);
        $this->m_szData .= $this->m_szApplication . $szEncoded . $szDefaults;

        $this->m_szData .= self::getChecksum($this->m_szData, $this->m_szPassword);

        return $this->m_szData;
    }
}
