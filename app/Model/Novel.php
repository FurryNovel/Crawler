<?php

namespace App\Model;

use App\Model\Model;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\Relations\HasOne;

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
 * @property array $ext_data 额外信息
 * @property int $view_count 阅读量
 * @property float $furry_weight furry权重
 * @property string $status 状态
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $fetched_at 获取时间
 */
class Novel extends Model {
	protected ?string $table = 'novel';
	
	protected array $casts = [
		'tags' => 'json',
		'ext_data' => 'json',
		'fetched_at' => 'timestamp',
	];
	
	public function author(): HasOne {
		return $this->hasOne(Author::class, 'id', 'author_id');
	}
	
	public function chapters(): HasMany {
		return $this->hasMany(Chapter::class, 'novel_id', 'id');
	}
}