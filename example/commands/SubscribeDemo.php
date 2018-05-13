<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\Demo\RabbitMQ\Commands;

use Aicode\RabbitMQ\SubscribeCommand;

class SubscribeDemo extends SubscribeCommand
{
    protected function configure()
    {
        $this->setName('demo:subscribe')
            ->setDescription('消息订阅demo');

        $this->addOption('retry-failed');
    }

    /**
     * 订阅消息处理
     *
     * @param \Aicode\RabbitMQ\SubMessage $msg
     *
     * @return bool 处理成功返回true(返回true后将会对消息进行处理确认)，失败返回false
     */
    public function subscribe(\Aicode\RabbitMQ\SubMessage $msg): bool
    {
        // TODO 业务逻辑实现
        echo sprintf(
            "subscriber:<%s> %s\n",
            $msg->getRoutingKey(),
            $msg->getMessage()->serialize()
        );
        echo "----------------------------------------\n";

        return true;
    }

    /**
     * 获取监听的路由
     *
     * @return string
     */
    protected function getRoutingKey(): string
    {
        return 'user.*';
    }

    /**
     * 获取监听的队列名称
     *
     * @return string
     */
    protected function getQueueName(): string
    {
        return 'user-monitor';
    }

    /**
     * 创建RabbitMQ连接
     *
     * @return \Aicode\RabbitMQ\RabbitMQ
     */
    protected function getRabbitMQ(): \Aicode\RabbitMQ\RabbitMQ
    {
        return new \Aicode\RabbitMQ\RabbitMQ();
    }

    /**
     * 返回Exchange name
     *
     * @return string
     */
    protected function getExchangeName(): string
    {
        return 'master';
    }
}