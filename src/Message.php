<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;

/**
 * Class Message
 *
 * @package Aicode\RabbitMQ
 */
class Message implements \Serializable
{
    private $message = [];

    public function __construct(array $message = [])
    {
        $this->message = [
            'body' => $message,
            '__id' => uniqid(date('YmdHis-', time())),
        ];
    }

    /**
     * Get Message
     *
     * @return array
     */
    public function getMessage()
    {
        return isset($this->message['body']) ? $this->message['body'] : null;
    }

    /**
     * 获取消息ID
     *
     * @return string
     */
    public function getID()
    {
        return isset($this->message['__id']) ? $this->message['__id'] : null;
    }

    /**
     * String representation of object
     *
     * @link  http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return json_encode($this->message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Constructs the object
     *
     * @link  http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     *
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $this->message = json_decode($serialized, true);
    }
}