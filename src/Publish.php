<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;

interface Publish
{
    /**
     * Publish a message to mq
     *
     * @param \Serializable|array $message
     * @param string              $routingKey
     *
     * @return AMQPMessage
     */
    public function publish($message, string $routingKey);
}