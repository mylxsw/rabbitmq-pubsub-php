<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\RabbitMQ;


use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * AMQP消息订阅命令
 *
 * 如果需要增加对失败消息的恢复处理，需要在命令签名中增加选项retry-failed，比如
 *
 *      subscriber:user {--retry-failed}
 *
 * 使用命令行执行 artisan subscriber:user --retry-failed 即可将失败的消息重新加入消费队列
 *
 * 继承该类后，实现抽象方法即可，该命令需要使用`supervisor`进行管理，退出后需要自动重启
 * kill信号必须使用`USR2`信号，环境为php7.1+。
 *
 * 命令包含一个计数器，初始值为100，每次发生异常减20，每次正常执行减2，当该计数器值
 * 小于1的时候，程序自动退出，这时候需要使用supervisor自动重启新的进程
 *
 * @package Aicode\RabbitMQ
 */
abstract class SubscribeCommand extends Command
{
    /**
     * 命令执行入口
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $retryFailed = $input->hasOption('retry-failed') && $input->getOption('retry-failed');
        if ($retryFailed) {
            $this->retryFailedMessage($input, $output);

            return;
        }

        $this->listenAndServe($input, $output);
    }

    /**
     * 订阅消息处理
     *
     * @param SubMessage $msg
     *
     * @return bool 处理成功返回true(返回true后将会对消息进行处理确认)，失败返回false
     */
    abstract public function subscribe(SubMessage $msg): bool;

    /**
     * 订阅失败的任务
     *
     * 如需按照需要重新订阅或者丢弃失败的消息，覆盖该方法，重新实现自己的逻辑即可，注意的是要返回false，否则
     * 还是会自动重新加入消费队列。
     *
     * 如果返回true，则任务自动重发，如果返回false，则需要自己处理，任务自动丢弃
     *
     * @param SubMessage      $msg
     * @param OutputInterface $output
     *
     * @return bool
     */
    public function subscribeFailed(SubMessage $msg, OutputInterface $output): bool
    {
        $output->writeln(sprintf(
            '恢复消息 %s, routing_key: %s, body: %s',
            $msg->getMessage()->getID(),
            $msg->getRoutingKey(),
            $msg->getAMQPMessage()->body
        ));
        $output->writeln('------------------------------------');

        return true;
    }

    /**
     * 获取监听的队列名称
     *
     * @return string
     */
    abstract protected function getQueueName(): string;

    /**
     * 获取监听的路由
     *
     * @return string
     */
    abstract protected function getRoutingKey(): string;

    /**
     * 创建RabbitMQ连接
     *
     * @return RabbitMQ
     */
    abstract protected function getRabbitMQ(): RabbitMQ;

    /**
     * 返回Exchange name
     *
     * @return string
     */
    abstract protected function getExchangeName(): string;

    /**
     * 重发失败的消息
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function retryFailedMessage(InputInterface $input, OutputInterface $output)
    {
        $callback = function (AMQPMessage $msg) use ($output) {
            $retry = $this->getRetryCount($msg);

            $subMessage = new SubMessage($msg, $msg->get('routing_key'), [
                'retry_count' => $retry, // 重试次数
            ]);

            return $this->subscribeFailed($subMessage, $output);
        };

        $subscriber = new Subscriber($this->getRabbitMQ(), $this->getExchangeName());
        $subscriber->retryFailed(
            $this->getQueueName(),
            $this->getRoutingKey(),
            $callback
        );

        $output->writeln('没有更多待处理的消息了');
    }

    /**
     * 启动消息消费
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function listenAndServe(InputInterface $input, OutputInterface $output)
    {
        $stopped = false;
        // 自动退出计数器，当值小于1的时候退出
        // 发生异常-20，正常执行每次-2
        $autoExitCounter = 200;

        // 信号处理，接收到SIGUSR2信号的时候自动退出
        // 注意：环境必须是php7.1+才支持
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGUSR2, function ($sig) use (&$stopped) {
                $stopped = true;
            });
        }

        $callback = function (AMQPMessage $msg, $publishRetry, $publishFailed) use (
            &$autoExitCounter
        ) {
            $retry = $this->getRetryCount($msg);

            try {
                $subMessage = new SubMessage($msg, $msg->get('routing_key'), [
                    'retry_count' => $retry, // 重试次数
                ]);

                $this->subscribe($subMessage);

                $autoExitCounter = $autoExitCounter - 2;

                // 发送确认消息
                $msg->delivery_info['channel']->basic_ack(
                    $msg->delivery_info['delivery_tag']
                );

            } catch (\Exception $ex) {

                // 发生普通异常，退出计数器-20
                $autoExitCounter = $autoExitCounter - 20;

                if ($retry > 3) {
                    // 超过最大重试次数，消息无法处理
                    $publishFailed($msg);

                    return;
                }

                // 消息处理失败，稍后重试
                $publishRetry($msg);
            }
        };

        $subscriber = new Subscriber($this->getRabbitMQ(), $this->getExchangeName());
        $subscriber->consume(
            $this->getQueueName(),
            $this->getRoutingKey(),
            $callback,
            function () use (&$stopped, &$autoExitCounter) {
                return $stopped || $autoExitCounter < 1;
            }
        );
    }

    /**
     * 获取消息重试次数
     *
     * @param AMQPMessage $msg
     *
     * @return int
     */
    protected function getRetryCount(AMQPMessage $msg): int
    {
        $retry = 0;
        if ($msg->has('application_headers')) {
            $headers = $msg->get('application_headers')->getNativeData();
            if (isset($headers['x-death'][0]['count'])) {
                $retry = $headers['x-death'][0]['count'];
            }
        }

        return (int)$retry;
    }
}