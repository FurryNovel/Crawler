<?php

namespace App\Task;

use App\Utils\Utils;
use Hyperf\AsyncQueue\Event\FailedHandle;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\Logger;

#[Listener]
class TaskFailedListener implements ListenerInterface {
	public function listen(): array {
		return [
			FailedHandle::class
		];
	}
	
	public function process(object $event): void {
		Utils::err($event->throwable);
	}
}