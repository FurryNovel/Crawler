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

#[Crontab(rule: "*/15 * * * *", name: "FetchUpdateNovelTask", callback: "execute", memo: "采集小说任务")]
class FetchUpdateNovelTask {
	#[Inject]
	protected FetchQueueService $fetchQueueService;
	
	public function execute(): void {
		Novel::where(function (Builder $query) {
			$query->where('fetched_at', '<', Carbon::now()->subHours(8));
		})->chunkById(10, function (Collection $novels) {
			$novels->each(function (Novel $novel) {
				$rule = FetchRule::getRule($novel->source);
				if (!$rule) {
					return;
				}
				$this->fetchQueueService->push([
					'novel_id' => $novel->id
				]);
			});
		});
	}
	
}