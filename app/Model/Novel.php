<?php

namespace App\Model;

use App\DataSet\DataSet;
use App\DataSet\LanguageService;
use App\FetchRule\FetchRule;
use App\FetchRule\NovelInfo;
use App\Model\Model;
use App\Service\FetchQueueService;
use App\Service\MediaService;
use App\Utils\Utils;
use Carbon\Carbon;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Events\Created;
use Hyperf\Database\Model\Events\Saved;
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
 * @property int $sync_status 同步状态:0正常,1同步中,2规则不存在
 * @property string $status 状态
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $fetched_at 获取时间
 */
class Novel extends Model {
	#[Inject]
	protected DataSet $dataSet;
	#[Inject(lazy: true)]
	protected MediaService $media;
	#[Inject(lazy: true)]
	protected LanguageService $language;
	
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
		$value = str_replace(
			[
				'i.pximg.net'
			],
			[
				'i.pixiv.re',
//				'img.tigerkk.me'
			],
			$value
		);
		return $this->media->getUri($value);
	}
	
	function isOneShot(): bool {
		$ext_data = $this->ext_data;
		return $ext_data['oneshot'] ?? false;
	}
	
	static function fromFetchRule(FetchRule $rule, NovelInfo $novelInfo): static {
		$novelInfo->author = $novelInfo->author ?? '佚名';
		$author = Author::where(function (Builder $query) use ($novelInfo) {
			$query->where('name', $novelInfo->author);
		})->first();
		if (!$author) {
			$authorInfo = $rule->fetchAuthorInfo($novelInfo->author_id);
			if (!$authorInfo) {
				$author = Author::register(
					User::TYPE_AUTHOR,
					$novelInfo->author,
					base64_encode('kk_novel_' . $novelInfo->author),
					[]
				);
			} else {
				$author = Author::register(
					User::TYPE_AUTHOR,
					$authorInfo->name,
					base64_encode('kk_novel_' . $authorInfo->name),
					[]
				);
			}
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
				'source' => $rule->getType(),
				'source_id' => $novelInfo->id,
				'status' => Novel::STATUS_PUBLISH,
				'sync_status' => 0,
				'ext_data' =>
					$novelInfo->options ?? [
						'oneshot' => false
					],
			]);
			$novel->updateTags($novelInfo->tags);
			$novel->save();
		}
		if (!$novel->sync_status) {
			$novel->touchField('sync_status', 1);
			$container = \Hyperf\Context\ApplicationContext::getContainer();
			$fetchQueueService = $container->get(FetchQueueService::class);
			$fetchQueueService->push([
				'novel_id' => $novel->id
			]);
		}
		return $novel;
	}
	
	function updateTags(?array $tags = null, string $content = ''): void {
		if (!$tags) {
			$tags = $this->attributes['tags'] ?? [];
		}
		$tags = $this->dataSet->convertToPattern(null, $tags);
		$tags = array_values(array_filter($tags, function ($tag) {
			return !in_array($tag, ['en', 'zh', 'ja', 'ko', 'zh_cn', 'zh_tw']);
		}));
		$text = $this->name . $this->desc . implode('', $tags) . $content;
		if (!empty($text)) {
			$language = $this->language->detect($text);
			$language = str_replace('-', '_', $language);
			if (str_starts_with($language, 'zh')) {
				$tags[] = 'zh';
			}
			$tags[] = $language;
		}
		$this->tags = array_unique($tags);
	}
	
	function updateFromFetchInfo(NovelInfo $novelInfo): bool {
		$this->doBackground(function () use ($novelInfo) {
			$this->name = $novelInfo->name;
			$this->cover = $novelInfo->cover;
			$this->desc = $novelInfo->desc;
			$this->updateTags($novelInfo->tags);
		});
		return true;
	}
	
	public function created(Created $event): void {
		try {
			$tags = json_decode($this->attributes['tags'] ?? '[]', true);
			foreach ($tags as $tag) {
				try {
					$_ = Tag::where('name', $tag)->first();
					if (!$_) {
						$_ = Tag::create([
							'name' => $tag,
							'count' => 1,
						]);
					}
				} catch (\Throwable $exception) {
					$_ = Tag::where('name', $tag)->first();
				}
				$_->increment('count');
			}
		} catch (\Throwable $exception) {
		}
	}
}