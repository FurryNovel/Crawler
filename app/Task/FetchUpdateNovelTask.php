<?php

namespace App\Task;

use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Collection\Collection;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Database\Query\Builder;

#[Crontab(rule: "0 0 4,12,20 * *", name: "FetchUpdateNovelTask", callback: "execute", memo: "采集小说任务")]
class FetchUpdateNovelTask {
	/**
	 * @throws GuzzleException
	 */
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
				$chapterList = $rule->fetchChapterList($novel->ext_data['source_id']);
				foreach ($chapterList as $chapter) {
					if (Chapter::where('source_id', $chapter->id)->first()) {
						continue;
					}
					$content = $rule->fetchChapterContent($novel->id, $chapter->id);
					Chapter::create([
						'author_id' => $novel->author_id,
						'novel_id' => $novel->id,
						'name' => $chapter->name,
						'content' => $content,
						'tags' => [],
						'text_count' => $chapter->text_count,
						'word_count' => $chapter->word_count,
						'status' => Chapter::STATUS_PUBLISH,
						'source_id' => $chapter->id
					]);
				}
				$novel->fetched_at = time();
				$novel->save();
			}
		}, 'id');
	}
	
}