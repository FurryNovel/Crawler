<?php

namespace App\Model;

use App\DataSet\DataSet;
use App\FetchRule\FetchRule;
use App\FetchRule\NovelInfo;
use App\Model\Model;
use App\Service\FetchQueueService;
use App\Utils\Utils;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\Di\Annotation\Inject;

/**
 * @property int $id 小说ID
 * @property int $author_id 作者ID
 * @property Author $author 作者
 * @property Chapter[] $chapters 章节
 * @property string $cover 封面
 * @property string $name 小说名称
 * @property string $desc 描述
 * @property array $tags 标签
 * @property string $source 来源
 * @property string $source_id 来源ID
 * @property array $ext_data 额外信息
 * @property int $view_count 阅读量
 * @property float $furry_weight furry权重
 * @property string $status 状态
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $fetched_at 获取时间
 */
class Novel extends Model {
	#[Inject]
	protected DataSet $dataSet;
	
	const STATUS_PENDING = 'pending';
	const STATUS_PUBLISH = 'publish';
	protected ?string $table = 'novel';
	
	protected array $with = [
		'author'
	];
	
	protected array $casts = [
		'tags' => 'json',
		'ext_data' => 'json',
		'view_count' => 'int'
	];
	
	protected array $dates = [
		'fetched_at',
	];
	
	protected array $hidden = [
		'chapters',
	];
	
	function author(): HasOne {
		return $this->hasOne(Author::class, 'id', 'author_id');
	}
	
	function chapters(): HasMany {
		return $this->hasMany(Chapter::class, 'novel_id', 'id');
	}
	
	function latestChapters(): HasMany {
		return $this->hasMany(Chapter::class, 'novel_id', 'id')->orderByDesc('created_at')->limit(3);
	}
	
	function getTagsAttribute($value): array {
		if (is_string($value)) {
			$value = json_decode($value, true);
		}
		return $this->dataSet->convertTo(Utils::getVisitorLanguage(), null, (array)$value);
	}
	
	function setTagsAttribute($value): void {
		$dataSet = \Hyperf\Support\make(DataSet::class);
		$this->attributes['tags'] = $this->asJson($dataSet->convertToPattern(null, (array)$value));
	}
	
	function getCoverAttribute($value): string {
		return str_replace(
			[
				'i.pximg.net'
			],
			[
				//'i.pixiv.re',
				'img.tigerkk.me'
			],
			$value
		);
	}
	
	function touchField($field = 'fetched_at'): bool {
		$this->timestamps = false;
		$this->{$field} = Carbon::now();
		$bool = $this->save();
		$this->timestamps = true;
		return $bool;
	}
	
	function isOneShot(): bool {
		$ext_data = $this->ext_data;
		return $ext_data['is_one_shot'] ?? false;
	}
	
	static function fromFetchRule(FetchRule $rule, NovelInfo $novelInfo): static {
		$novelInfo->author = $novelInfo->author ?? '佚名';
		$author = Author::where(function (Builder $query) use ($novelInfo) {
			$query->where('name', $novelInfo->author);
		})->first();
		if (!$author) {
			$authorInfo = $rule->fetchAuthorInfo($novelInfo->author_id);
			$author = Author::register(
				User::TYPE_AUTHOR,
				$authorInfo->name,
				base64_encode('kk_novel_' . $authorInfo->name),
				[]
			);
		}
		/**
		 * @var Novel $novel
		 */
		$novel = Novel::where(function (Builder $query) use ($novelInfo) {
			$query->where('source_id', $novelInfo->id);
		})->first();
		if (!$novel) {
			$novel = new Novel([
				'author_id' => $author->id,
				'name' => $novelInfo->name,
				'cover' => $novelInfo->cover,
				'desc' => $novelInfo->desc,
				'tags' => $novelInfo->tags,
				'view_count' => 0,
				'furry_weight' => 0,
				'source' => $rule->getType(),
				'source_id' => $novelInfo->id,
				'status' => Novel::STATUS_PUBLISH,
				'ext_data' => [],
			]);
			$novel->save();
		}
		$container = \Hyperf\Context\ApplicationContext::getContainer();
		$fetchQueueService = $container->get(FetchQueueService::class);
		$fetchQueueService->push([
			'novel_id' => $novel->id
		]);
		return $novel;
	}
}