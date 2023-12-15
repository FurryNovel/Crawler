<?php

namespace App\Task;

use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use App\Service\FetchQueueService;
use Carbon\Carbon;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Cache;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Builder;
use Hyperf\Di\Annotation\Inject;

#[Crontab(rule: "*/15 * * * *", name: "FetchLatestNovelTask", singleton: true, callback: "execute", memo: "采集最新小说任务")]
class FetchLatestNovelTask {
	const TAGS = [
		'furry',
		'兽人'
	];
	
	public function execute(): void {
		$cache = \FriendsOfHyperf\Helpers\cache();
		foreach (self::TAGS as $tag) {
			foreach (FetchRule::RULES as $rule => $class) {
				$rule = FetchRule::getRule($rule);
				$page = 1;
				$max_page = PHP_INT_MAX;
				$last_id = $cache->get(sprintf('fetch_latest_%s_%s', $rule->getType(), $tag), null);
				if (!$last_id) {
					$max_page = 5;
				}
				while ($page < $max_page) {
					foreach ($rule->fetchNovelList($tag, $page) as $novelInfo) {
						if ($novelInfo->id == $last_id) {
							$max_page = $page;
							break;
						}
						Novel::fromFetchRule($rule, $novelInfo);
					}
					$page++;
				}
				if (!empty($novelInfo)) {
					$cache->set(sprintf('fetch_latest_%s_%s', $rule->getType(), $tag), $novelInfo->id);
				}
			}
		}
	}
}