<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;

interface Subscribe
{
    /**
     * 订阅消费
     *
     * 注意： 该方法会堵塞执行
     *
     * @param string   $queueName          队列名称
     * @param string   $routingKey         过滤路由key
     * @param \Closure $callback           回调处理函数，包含参数(AMQPMessage $msg, $publishRetry, $publishFailed)
     * @param \Closure $shouldExitCallback 是否应该退出的回调函数，返回true则退出，false继续执行
     */
    public function consume(
        string $queueName,
        string $routingKey,
        \Closure $callback,
        \Closure $shouldExitCallback = null
    );

    /**
     * 重试失败的消息
     *
     * 注意： 该方法会堵塞执行
     *
     * @param string   $queueName
     * @param string   $routingKey
     * @param \Closure $callback 回调函数，可以为空，返回true则重新发布，false则丢弃
     */
    public function retryFailed(string $queueName, string $routingKey, $callback = null);
}