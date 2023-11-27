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
	public function handle() {
		$novel_id = $this->params['novel_id'];
		$novel = Novel::query()->where('id', $novel_id)->first();
		if (!$novel) {
			return;
		}
		$rule = FetchRule::getRule($novel->source);
		if (!$rule) {
			return;
		}
		$chapterList = $rule->fetchChapterList($novel->id);
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
	}
}