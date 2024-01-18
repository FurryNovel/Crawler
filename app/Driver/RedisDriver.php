<?php

namespace App\Driver;

use Hyperf\AsyncQueue\JobInterface;
use Hyperf\AsyncQueue\JobMessage;
use Hyperf\AsyncQueue\MessageInterface;
use function Hyperf\Support\make;

class RedisDriver extends \Hyperf\AsyncQueue\Driver\RedisDriver {
	public function push(JobInterface $job, int $delay = 0): bool {
		$hash = md5($this->packer->pack($job));
		$key = $this->channel->getChannel() . ':duplicate';
		if ($this->redis->hExists($key, $hash)) {
			return false;
		}
		$this->redis->hSet($key, $hash, 1);
		return parent::push($job, $delay);
	}
	
	public function pop(): array {
		list($data, $message) = parent::pop();
		if ($message instanceof MessageInterface) {
			$job = $message->job();
			$hash = md5($this->packer->pack($job));
			$key = $this->channel->getChannel() . ':duplicate';
			$this->redis->hDel($key, $hash);
		}
		return [$data, $message];
	}
}