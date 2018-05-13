<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class RabbitMQ
 *
 * @package Aicode\RabbitMQ
 */
class RabbitMQ
{
    /**
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * @var int
     */
    private $port = 5672;

    /**
     * @var string
     */
    private $user = 'guest';

    /**
     * @var string
     */
    private $password = 'guest';

    /**
     * @var string
     */
    private $vhost = '/';

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel[]
     */
    private $channels = [];

    /**
     * RabbitMQ constructor.
     *
     * host/port/user/password/vhost
     *
     * @param array $configs
     */
    public function __construct(array $configs = [])
    {
        foreach ($configs as $key => $val) {
            if (isset($this->$key)) {
                $this->$key = $val;
            }
        }

        $this->connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost
        );
    }

    /**
     * Create a new channel
     *
     * @param string $channel_id
     *
     * @return AMQPChannel
     */
    public function channel($channel_id = null)
    {
        if (isset($this->channels[$channel_id])) {
            return $this->channels[$channel_id];
        }

        return $this->channels[$channel_id] = $this->connection->channel($channel_id);
    }

    /**
     * Get RabbitMQ Connection
     *
     * @return AMQPStreamConnection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * RabbitMQ destructor
     */
    public function __destruct()
    {
        foreach ($this->channels as $channel) {
            if (!empty($channel)) {
                $channel->close();
            }
        }

        if (!empty($this->connection)) {
            $this->connection->close();
        }
    }
}