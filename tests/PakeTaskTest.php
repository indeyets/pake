<?php
use PHPUnit\Framework\TestCase;


class PakeTaskTest extends TestCase
{
  public function testAbbrev()
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
    $this->assertEquals($expected, $result);
  }
}
