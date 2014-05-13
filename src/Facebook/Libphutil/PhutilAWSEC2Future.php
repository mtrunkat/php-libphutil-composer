<?php

namespace Facebook\Libphutil;

/**
 * @group aws
 */
class PhutilAWSEC2Future extends \Facebook\Libphutil\PhutilAWSFuture {

  public function getServiceName() {
    return 'ec2';
  }

}
