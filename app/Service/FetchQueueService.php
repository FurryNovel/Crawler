<?php

declare(strict_types = 1);

namespace App\Service;

use App\Task\FetchSingleNovelTask;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;

class FetchQueueService {
	protected DriverInterface $driver;
	
	public function __construct(DriverFactory $driverFactory) {
		$this->driver = $driverFactory->get('default');
	}
	
	/**
	 * 生产消息.
	 * @param array $params 数据
	 * @param int $delay 延时时间 单位秒
	 * @return bool
	 */
	public function push(array $params, int $delay = 0): bool {
		return $this->driver->push(new FetchSingleNovelTask($params), $delay);
	}
}
