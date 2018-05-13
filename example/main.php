#!/usr/bin/env php
<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new \Aicode\Demo\RabbitMQ\Commands\SubscribeDemo());
$application->add(new \Aicode\Demo\RabbitMQ\Commands\PublishDemo());

try {
    $application->run();
} catch (Exception $e) {
    echo sprintf('命令执行失败: %s', $e->getMessage());
}


