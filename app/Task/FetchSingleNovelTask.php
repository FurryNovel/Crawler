<?php

namespace App\Task;

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
		$novel->fetched_at = Carbon::now();
		$novel->save();
		
		$rule = FetchRule::getRule($novel->source);
		if (!$rule) {
			return;
		}
		
		$page = 0;
		do {
			$page++;
			$chapterList = $this->handleException(function () use ($page, $novel, $rule) {
				return $rule->fetchChapterList($novel->source_id, (string)$page);
			}) ?? [];
			
			foreach ($chapterList as $chapterInfo) {
				if (Chapter::where('source_id', $chapterInfo->id)->first()) {
					continue;
				}
				$content = $this->handleException(function () use ($chapterInfo, $novel, $rule) {
					return $rule->fetchChapterContent($novel->source_id, $chapterInfo->id);
				});
				if ($content) {
					Chapter::create([
						'author_id' => $novel->author_id,
						'novel_id' => $novel->id,
						'name' => $chapterInfo->name,
						'content' => $content,
						'tags' => [],
						'text_count' => $chapterInfo->text_count ?? mb_strlen($content),
						'word_count' => $chapterInfo->word_count ?? mb_strlen($content),
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