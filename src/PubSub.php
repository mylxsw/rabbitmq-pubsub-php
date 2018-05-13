<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;


use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Class PubSub
 *
 * @package Aicode\RabbitMQ
 */
abstract class PubSub
{

    /**
     * @var RabbitMQ
     */
    protected $rabbitMQ;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $exchangeName;

    /**
     * Publisher constructor.
     *
     * @param RabbitMQ $rabbitMQ
     * @param string   $exchangeName
     */
    public function __construct(RabbitMQ $rabbitMQ = null, string $exchangeName = 'master')
    {
        $this->rabbitMQ     = $rabbitMQ;
        $this->exchangeName = $exchangeName;
        $this->initialize();
    }

    /**
     * Generate Routing Key
     *
     * @param string $entity
     * @param string $action
     *
     * @return string
     */
    public function routingKey(string $entity, string $action): string
    {
        return sprintf('%s.%s', $entity, $action);
    }

    /**
     * Parse the routing key
     *
     * @param string $routingKey
     *
     * @return array [entity, action]
     */
    public function parseRoutingKey(string $routingKey): array
    {
        $segs   = explode('.', $routingKey);
        $action = $segs[count($segs) - 1];
        // 注意删除顺序，先后面的元素，后前面的元素
        // 如果反过来会导致删除后面元素的时候索引值变了，导致删除错误的索引
        unset($segs[count($segs) - 1], $segs[0]);

        return [
            implode('.', $segs),
            $action
        ];
    }

    /**
     * Initialize
     *
     * @return void
     */
    protected function initialize()
    {
        $this->channel = $this->rabbitMQ->channel();

        // 普通交换机
        $this->channel->exchange_declare($this->exchangeTopic(), 'topic', false, true, false);
        // 重试交换机
        $this->channel->exchange_declare($this->exchangeRetryTopic(), 'topic', false, true, false);
        // 失败交换机
        $this->channel->exchange_declare($this->exchangeFailedTopic(), 'topic', false, true, false);
    }

    /**
     * 获取交换机Topic
     *
     * @return string
     */
    protected function exchangeTopic(): string
    {
        return $this->exchangeName;
    }

    /**
     * 重试交换机Topic
     *
     * @return string
     */
    protected function exchangeRetryTopic(): string
    {
        return $this->exchangeTopic() . '.retry';
    }

    /**
     * 失败交换机Topic
     *
     * @return string
     */
    protected function exchangeFailedTopic(): string
    {
        return $this->exchangeTopic() . '.failed';
    }
}