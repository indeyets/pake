<?php

class pakeTaskTest extends UnitTestCase
{
  public function test_abbrev()
  {
    $words = array('start', 'stop', 'queue', 'quit');
    $result = pakeTask::abbrev($words);
  
    $expected = array(
      'st' => array('start', 'stop'),
      's' => array('start', 'stop'),
      'sta' => array('start'), 'star' => array('start'), 'start' => array('start'),
      'sto' => array('stop'), 'stop' => array('stop'),
      'qu' => array('queue', 'quit'),
      'q' => array('queue', 'quit'),
      'que' => array('queue'), 'queu' => array('queue'), 'queue' => array('queue'),
      'qui' => array('quit'), 'quit' => array('quit')
    );
    $this->assertEqual($expected, $result);
  }
}
