<?php

namespace App\Task;

use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use App\Service\FetchQueueService;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Collection\Collection;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Inject;

#[Crontab(rule: "0 0 4,12,20 * *", name: "FetchUpdateNovelTask", callback: "execute", memo: "采集小说任务")]
class FetchUpdateNovelTask {
	#[Inject]
	protected FetchQueueService $fetchQueueService;
	
	public function execute(): void {
		$time = time();
		Novel::where(function (Builder $query) use ($time) {
			$query->where('fetched_at', '<', $time);
		})->chunkById(10, function (Collection $novels) {
			/**
			 * @var Novel[] $novels
			 */
			foreach ($novels as $novel) {
				$rule = FetchRule::getRule($novel->source);
				if (!$rule) {
					continue;
				}
				$this->fetchQueueService->push([
					'novel_id' => $novel->id
				]);
			}
		}, 'id');
	}
	
}