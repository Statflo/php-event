<?php

// @TODO remove-me

require_once dirname(__FILE__) .'/../../vendor/autoload.php';

use Statflo\DI\Bootstrap;

$bootstrap = Bootstrap::run([
    'debug'       =>  (bool) getenv('PHP_APP_DEBUG'),
    'config_path' => dirname(__FILE__) . "/config",
    'parameters'  => [
        'statflo.amqp_host'              => getenv('AMQP_HOST') ?: 'dev.statflo.local',
        'statflo.amqp_port'              => getenv('AMQP_PORT') ?: 5672,
        'statflo.amqp_user'              => getenv('AMQP_USER') ?: 'guest',
        'statflo.amqp_password'          => getenv('AMQP_PASSWORD') ?: 'guest',
        'statflo.event_manager_exchange' => getenv('EVENT_MANAGER_EXCHANGE') ?: 'api_events',
        'statflo.event_manager_queue'    => getenv('EVENT_MANAGER_QUEUE') ?: 'php_api_events',
    ]
]);

$bootstrap
    ->get('statflo.service.eventListener')
    ->on('foo.bar', function($message, $channel){
        echo "\n--------\n";
        var_dump($message->content, $channel->ack($message));
        echo "\n--------\n";
    })
    ->listen()
;
/**
function (Message $message, Channel $channel, Client $client) {
    echo " [x] Received ", $message->content, "\n";
    sleep(substr_count($message->content, '.'));
    echo " [x] Done", $message->content, "\n";
    $channel->ack($message);
}
*/
/**
docker-compose

rabbitmq: # https://registry.hub.docker.com/_/rabbitmq/
  image: rabbitmq:3-management
  ports:
    - 5672:5672
    - 15672:15672
    - 8080:8080

*/
