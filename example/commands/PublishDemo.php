<?php
/**
 * rabbitmq-pubsub-php
 *
 * @link      https://aicode.cc
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace Aicode\Demo\RabbitMQ\Commands;


use Aicode\RabbitMQ\Message;
use Aicode\RabbitMQ\Publisher;
use Aicode\RabbitMQ\RabbitMQ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublishDemo extends Command
{
    protected function configure()
    {
        $this->setName('demo:publisher')
            ->setDescription('消息发布demo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $publisher = new Publisher(new RabbitMQ(), 'master');

        $publisher->publish(new Message(['id' => uniqid(), 'name' => 'mylxsw']), 'user.created');
        $publisher->publish(new Message(['id' => uniqid()]), 'user.deleted');

        $output->writeln('消息发送完成');
    }

}