<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class SubMessage
 *
 * @package Aicode\RabbitMQ
 */
class SubMessage
{
    private $message;
    private $routingKey;
    private $params;

    /**
     * SubMessage constructor.
     *
     * @param AMQPMessage $message
     * @param string      $routingKey
     * @param array       $params
     */
    public function __construct(AMQPMessage $message, string $routingKey, array $params = [])
    {
        $this->params = $params;
        $this->message = $message;

        $this->routingKey = $routingKey;
    }

    /**
     * Get AMQP Message
     *
     * @return AMQPMessage
     */
    public function getAMQPMessage(): AMQPMessage
    {
        return $this->message;
    }

    /**
     * Get original Message
     *
     * @return Message
     */
    public function getMessage(): Message
    {
        $message = new Message();
        $message->unserialize($this->message->body);

        return $message;
    }

    /**
     * Get meta params
     *
     * @return array
     */
    public function getParams() :array
    {
        return is_array($this->params) ? $this->params : [];
    }

    /**
     * Get meta param
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getParam(string $key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

    /**
     * Get routing key
     *
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }
}