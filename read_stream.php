<?php
require __DIR__ . '/vendor/autoload.php';

use Afk11\Sbs1\Exception\InvalidMessageException;
use Afk11\Sbs1\Exception\ValidationError;
use Afk11\Sbs1\LineReader;
use Afk11\Sbs1\MessageType\MessageType;
use Afk11\Sbs1\MessageType\MessageTypeRegistryFactory;
use Afk11\Sbs1\StreamReader;
use Afk11\Sbs1\TransmissionType\TransmissionTypeRegistryFactory;

register_shutdown_function(function() {
	$runtime = bcsub(microtime(true), $_SERVER['REQUEST_TIME_FLOAT'], 2);

	error_log("After running for $runtime seconds the stream has stopped: " . error_get_last()['message']);
});

$lineReader = new LineReader(
	new MessageTypeRegistryFactory()->create(),
	new TransmissionTypeRegistryFactory()->create(),
);
$streamReader = new StreamReader();

$redis = new Redis();

while(true) {
	try {
		foreach($streamReader->readTcpStream($lineReader, '192.168.1.pi', 30003) as $line) {
			/** @var \Afk11\Sbs1\Message\ImmutableMessage $line */
			if($line->getMessageType()->getId() === MessageType::MSG && $line->getHexIdent()) {
				$redis->HINCRBY('adsb:planes:' . date('Y-m-d'), $line->getHexIdent(), 1);
			}
		}
	} catch(RuntimeException|ValidationError|InvalidMessageException) {
		//do nothing
	} catch(Throwable $e) {
		error_log('Caught ' . get_class($e) . ': ' . $e->getMessage());
	}
}
