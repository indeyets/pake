<?php

require 'init.php';

$stream = new GrowlStreamer();

// $p = new GrowlRegistrationPacket("Pake");
// $p->addNotification("Informational", false);
// $p->addNotification("Warning");
// 
// $stream->send($p);

// $p = new GrowlNotificationPacket("Pake", "Informational", "Test", "Test message", -2, True );
// $p = new GrowlNotificationPacket("growlnotify", "Command-Line Growl Notification", "Test", "Test message", 1, true);
$p = new GrowlNotificationPacket();

$stream->send($p);
unset($stream);
