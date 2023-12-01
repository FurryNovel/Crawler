<?php

namespace App\Model;

use App\DataSet\DataSet;
use App\Model\Model;
use App\Utils\Utils;
use Carbon\Carbon;
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
	const STATUS_PENDING = 'pending';
	const STATUS_PUBLISH = 'publish';
	protected ?string $table = 'novel';
	
	#[Inject]
	protected DataSet $dataSet;
	
	protected array $casts = [
		'tags' => 'json',
		'ext_data' => 'json',
		'fetched_at' => 'timestamp',
	];
	
	protected array $hidden = [
		'chapters'
	];
	
	function author(): HasOne {
		return $this->hasOne(Author::class, 'id', 'author_id');
	}
	
	function chapters(): HasMany {
		return $this->hasMany(Chapter::class, 'novel_id', 'id');
	}
	
	function save(array $options = []): bool {
		$this->tags = $this->dataSet->convertToPattern(null, $this->tags);
		return parent::save($options);
	}
	
	function getTagsAttribute($value): array {
		if (is_string($value)) {
			$value = json_decode($value, true);
		}
		return $this->dataSet->convertTo(Utils::getVisitorLanguage(), null, (array)$value);
	}
	
	function getCoverAttribute($value): string {
		return str_replace(
			[
				'i.pximg.net'
			],
			[
				'i.pixiv.re',
				//'pixiv.545551320.workers.dev'
			],
			$value
		);
	}
}