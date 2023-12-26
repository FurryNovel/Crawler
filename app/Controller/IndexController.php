<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\Controller\Abstract\PublicController;
use App\DataSet\DataSet;
use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use App\Model\Tag;
use App\Model\User;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Qbhy\HyperfAuth\AuthManager;

#[AutoController]
class IndexController extends FS_Controller {
	function ping(): array {
		return $this->success(
			[
				'time' => time(),
			],
			'pong'
		);
	}

//	#[Inject]
//	protected DataSet $dataSet;

//	function tags() {
//		Novel::chunk(100, function (Collection $novels) {
//			$novels->each(function (Novel $novel) {
//				try {
//					$tags = $this->dataSet->convertToPattern(null, $novel->tags);
//					foreach ($tags as $tag) {
//						try {
//							$_ = Tag::where('name', $tag)->first();
//							if (!$_) {
//								$_ = Tag::create([
//									'name' => $tag,
//									'count' => 1,
//								]);
//							}
//						} catch (\Exception $exception) {
//							$_ = Tag::where('name', $tag)->first();
//						}
//						$_->increment('count');
//					}
//				} catch (\Exception $exception) {
//					var_dump($exception->getMessage());
//				}
//			});
//		});
//	}
//
//	function bilibili() {
//		$rule = FetchRule::getRule('bilibili');
//		if (!$rule) {
//			return $this->error('规则不存在');
//		}
//		Novel::where('source', 'bilibili')->chunk(100, function (Collection $novels) use ($rule) {
//			$novels->each(function (Novel $novel) use ($rule) {
//				Chapter::where('novel_id', $novel->id)->chunk(100, function (Collection $chapters) use ($rule) {
//					$chapters->each(function (Chapter $chapter) use ($rule) {
//						$chapter->content = $rule->processContent($chapter->content);
//						$chapter->save();
//					});
//				});
//			});
//		});
//
//		return $this->success();
//	}
}
