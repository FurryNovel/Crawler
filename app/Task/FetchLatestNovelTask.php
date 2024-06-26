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
		'兽人',
		'獣人',
		'ケモノ',
		'transfur',
		'龙',
		'虎',
		'狼',
	];
	
	public function execute(): void {
		$cache = \FriendsOfHyperf\Helpers\cache();
		foreach (self::TAGS as $tag) {
			foreach (FetchRule::RULES as $type => $class) {
				try {
					$rule = FetchRule::getRule($type);
					$page = 1;
					$max_page = 30;
					$last_id = $cache->get(sprintf('fetch_latest_%s_%s', $type, $tag), null);
					if (!$last_id) {
						$max_page = 5;
					}
					$is_first = true;
					while ($page < $max_page) {
						$novels = $rule->fetchNovelList($tag, $page);
						if ($is_first and !empty($novels)) {
							$cache->set(sprintf('fetch_latest_%s_%s', $type, $tag), $novels[0]->id ?? null);
							$is_first = false;
						}
						foreach ($novels as $novelInfo) {
							if ($novelInfo->id == $last_id) {
								$max_page = $page;
								break;
							}
							Novel::fromFetchRule($rule, $novelInfo);
						}
						$page++;
					}
				} catch (\Throwable $throwable) {
					var_dump($throwable->getTraceAsString());
				}
			}
		}
	}
}