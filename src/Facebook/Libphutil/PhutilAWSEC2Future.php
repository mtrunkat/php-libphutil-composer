<?php

namespace Facebook\Libphutil;

/**
 * @group aws
 */
final class PhutilAWSEC2Future extends \Facebook\Libphutil\PhutilAWSFuture {

  public function getServiceName() {
    return 'ec2';
  }

}
