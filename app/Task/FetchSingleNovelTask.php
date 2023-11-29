<?php

namespace App\Task;

use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\AsyncQueue\Job;

class FetchSingleNovelTask extends Job {
	public array $params;
	
	public function __construct(array $params) {
		$this->params = $params;
	}
	
	/**
	 * @throws GuzzleException
	 */
	public function handle(): void {
		try {
			$novel_id = $this->params['novel_id'];
			$novel = Novel::query()->where('id', $novel_id)->first();
			if (!$novel) {
				return;
			}
			$rule = FetchRule::getRule($novel->source);
			if (!$rule) {
				return;
			}
			$chapterList = $rule->fetchChapterList($novel->source_id);
			foreach ($chapterList as $chapterInfo) {
				if (Chapter::where('source_id', $chapterInfo->id)->first()) {
					continue;
				}
				$content = $rule->fetchChapterContent($novel->source_id, $chapterInfo->id);
				Chapter::create([
					'author_id' => $novel->author_id,
					'novel_id' => $novel->id,
					'name' => $chapterInfo->name,
					'content' => $content,
					'tags' => [],
					'text_count' => $chapterInfo->text_count,
					'word_count' => $chapterInfo->word_count,
					'status' => Chapter::STATUS_PUBLISH,
					'source_id' => $chapterInfo->id
				]);
			}
		} catch (GuzzleException $e) {
			var_dump($e->getMessage());
		} catch (\Exception $e) {
			var_dump($e);
		}
	}
}