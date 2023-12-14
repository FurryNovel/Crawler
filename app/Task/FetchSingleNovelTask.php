<?php

namespace App\Task;

use App\FetchRule\ChapterInfo;
use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\AsyncQueue\Job;

class FetchSingleNovelTask extends Job {
	public array $params;
	
	public function __construct(array $params) {
		$this->params = $params;
	}
	
	public function handle(): void {
		$novel_id = $this->params['novel_id'];
		
		$novel = Novel::findFromCache($novel_id);
		if (!$novel) {
			return;
		}
		$novel->touchField('fetched_at');
		
		$rule = FetchRule::getRule($novel->source);
		if (!$rule) {
			return;
		}
		
		$page = 0;
		do {
			$page++;
			/**
			 * @var ChapterInfo[] $chapterList
			 */
			$chapterList = $this->handleException(function () use ($page, $novel, $rule) {
				return $rule->fetchChapterList($novel->source_id, (string)$page);
			}) ?? [];
			
			foreach ($chapterList as $chapterInfo) {
				if (Chapter::where('source_id', $chapterInfo->id)->first()) {
					continue;
				}
				$chapter = $this->handleException(function () use ($chapterInfo, $novel, $rule) {
					return $rule->fetchChapterContent($novel->source_id, $chapterInfo->id);
				});
				/**
				 * @var ChapterInfo $chapter
				 */
				if ($chapter) {
					Chapter::create([
						'author_id' => $novel->author_id,
						'novel_id' => $novel->id,
						'name' => $chapter->name,
						'content' => $chapter->content,
						'tags' => [],
						'text_count' => max($chapterInfo->text_count ?? 0, mb_strlen($chapter->content)),
						'word_count' => max($chapterInfo->word_count ?? 0, mb_strlen($chapter->content)),
						'status' => Chapter::STATUS_PUBLISH,
						'source_id' => $chapterInfo->id
					]);
				}
			}
		} while (!empty($chapterList));
	}
	
	function handleException(callable $callback) {
		try {
			return $callback();
		} catch (GuzzleException $e) {
			var_dump($e->getMessage());
		} catch (\Exception $e) {
			var_dump($e);
		}
		return null;
	}
}