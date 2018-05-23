<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://www.yunsom.com/
 * @copyright 管宜尧 <guanyiyao@yunsom.com>
 */

namespace Aicode\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;

trait Helpers
{
    private function getFailedQueueName(string $queueName): string
    {
        return "{$queueName}@failed";
    }

    private function getRetryQueueName(string $queueName): string
    {
        return "{$queueName}@retry";
    }

    private function getOrigRoutingKey(AMQPMessage $msg)
    {
        return (function (AMQPMessage $msg) {
                $retry = null;
                if ($msg->has('application_headers')) {
                    $headers = $msg->get('application_headers')->getNativeData();
                    if (isset($headers['x-orig-routing-key'])) {
                        $retry = $headers['x-orig-routing-key'];
                    }
                }

                return $retry;
            })($msg) ?? $msg->get('routing_key');
    }
}