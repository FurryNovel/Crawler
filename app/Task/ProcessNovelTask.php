<?php

namespace App\Task;

use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use App\Service\FetchQueueService;
use Carbon\Carbon;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Builder;
use Hyperf\Di\Annotation\Inject;

#[Crontab(rule: "*/15 * * * *", name: "ProcessNovelTask", callback: "execute", memo: "处理小说信息")]
class ProcessNovelTask {
	#[Inject]
	protected \Hyperf\Redis\Redis $redis;
	
	public function execute(): void {
		$novel = new Novel();
		foreach ($novel->getLazy() as $field) {
			$cursor = null;
			if (!$this->redis->exists("novel:$field")) {
				continue;
			}
			while ($novels = $this->redis->hScan("novel:$field", $cursor, '*', 1000)) {
				foreach ($novels as $key => $value) {
					$novel = Novel::find($key);
					$novel?->doBackground(function () use ($novel, $value) {
						$novel->view_count = $value;
					});
				}
			}
		}
		unset($novel);
	}
}
