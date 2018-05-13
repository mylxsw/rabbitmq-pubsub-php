<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;


use PhpAmqpLib\Exception\AMQPIOWaitException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class Subscriber
 *
 * @package Aicode\RabbitMQ
 */
class Subscriber extends PubSub implements Subscribe
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
    ) {
        if ($shouldExitCallback === null) {
            $shouldExitCallback = function () {
                return false;
            };
        }

        $this->declareRetryQueue($queueName, $routingKey);
        $this->declareConsumeQueue($queueName, $routingKey);
        $this->declareFailedQueue($queueName, $routingKey);

        // 发起延时重试
        $publishRetry = function (AMQPMessage $msg) {
            $this->channel->basic_publish($msg, $this->exchangeRetryTopic(),
                $msg->get('routing_key'));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        // 将消息发送到失败队列
        $publishFailed = function (AMQPMessage $msg) {
            $this->channel->basic_publish($msg, $this->exchangeFailedTopic(),
                $msg->get('routing_key'));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $this->channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($callback, $publishRetry, $publishFailed) {
                $callback($msg, $publishRetry, $publishFailed);
            }
        );
        while (count($this->channel->callbacks)) {

            if ($shouldExitCallback()) {
                return;
            }

            try {
                $this->channel->wait(null, false, 3);
            } catch (AMQPTimeoutException $e) {
            } catch (AMQPIOWaitException $e) {
            }
        }
    }

    /**
     * 重试失败的消息
     *
     * 注意： 该方法会堵塞执行
     *
     * @param string   $queueName
     * @param string   $routingKey
     * @param \Closure $callback 回调函数，可以为空，返回true则重新发布，false则丢弃
     */
    public function retryFailed(string $queueName, string $routingKey, $callback = null)
    {
        $this->declareConsumeQueue($queueName, $routingKey);
        $failedQueueName = $this->declareFailedQueue($queueName, $routingKey);

        $this->channel->basic_consume(
            $failedQueueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($queueName, $routingKey, $callback) {
                if (is_null($callback) || $callback($msg)) {
                    // 重置header中的x-death属性
                    $msg->set('application_headers', new AMQPTable([]));
                    $this->channel->basic_publish(
                        $msg,
                        $this->exchangeTopic(),
                        $msg->get('routing_key')
                    );
                }

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }
        );
        while (count($this->channel->callbacks)) {
            try {
                $this->channel->wait(null, false, 3);
            } catch (AMQPTimeoutException $e) {
                return;
            } catch (AMQPIOWaitException $e) {
            }
        }
    }

    /**
     * 声明重试队列
     *
     * @param string $queueName
     * @param string $routingKey
     *
     * @return string
     */
    private function declareRetryQueue(string $queueName, string $routingKey): string
    {
        $retryQueueName = $queueName . '@retry';
        $this->channel->queue_declare(
            $retryQueueName,
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => $this->exchangeTopic(),
                'x-message-ttl'          => 30 * 1000,
            ])
        );
        $this->channel->queue_bind($retryQueueName, $this->exchangeRetryTopic(), $routingKey);

        return $retryQueueName;
    }

    /**
     * 声明消费队列
     *
     * @param string $queueName
     * @param string $routingKey
     *
     * @return string
     */
    private function declareConsumeQueue(string $queueName, string $routingKey): string
    {
        $this->channel->queue_declare($queueName, false, true, false, false, false);
        $this->channel->queue_bind($queueName, $this->exchangeTopic(), $routingKey);

        return $queueName;
    }

    /**
     * 声明消费失败队列
     *
     * @param string $queueName
     * @param string $routingKey
     *
     * @return string
     */
    private function declareFailedQueue(string $queueName, string $routingKey): string
    {
        $failedQueueName = "{$queueName}@failed";
        $this->channel->queue_declare($failedQueueName, false, true, false, false, false);
        $this->channel->queue_bind($failedQueueName, $this->exchangeFailedTopic(), $routingKey);

        return $failedQueueName;
    }

}