<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

class Publisher extends PubSub implements Publish
{
    /**
     * Publish a message to mq
     *
     * @param \Serializable|array $message
     * @param string              $routingKey
     *
     * @return AMQPMessage
     */
    public function publish($message, string $routingKey)
    {
        if (is_array($message)) {
            $message = new Message($message);
        }

        if (!$message instanceof \Serializable) {
            throw new \InvalidArgumentException('message必须实现Serializable接口');
        }

        $msg = new AMQPMessage($message->serialize(), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $exchange = $this->exchangeTopic();
        $this->channel->basic_publish($msg, $exchange, $routingKey);

        return $msg;
    }
}