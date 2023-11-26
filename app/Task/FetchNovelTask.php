<?php

namespace App\Task;

use App\FetchRule\FetchRule;
use Hyperf\Crontab\Annotation\Crontab;

#[Crontab(rule: "* * * * *", name: "FetchNovelTask", callback: "execute", memo: "采集小说任务")]
class FetchNovelTask {
	
	public function execute(): void {
		$rule = FetchRule::getRule('pixiv');
//		$novelList = $rule->fetchNovelList();
//		foreach ($novelList as $novel) {
//			$chapterList = $rule->fetchChapterList($novel->id);
//			foreach ($chapterList as $chapter) {
//				$content = $rule->fetchChapterContent($novel->id, $chapter->id);
//			}
//		}
	}
	
}