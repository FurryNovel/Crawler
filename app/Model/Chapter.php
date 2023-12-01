<?php

namespace App\Model;

use App\DataSet\DataSet;
use App\Model\Model;
use Carbon\Carbon;
use Fukuball\Jieba\JiebaAnalyse;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\Di\Annotation\Inject;

/**
 * @property int $id ID
 * @property int $author_id 作者ID
 * @property Author $author 作者
 * @property int $novel_id 小说ID
 * @property Novel $novel 小说
 * @property string $name 名称
 * @property string $content 内容
 * @property array $tags 标签
 * @property int $text_count
 * @property int $word_count
 * @property string $status 状态
 * @property string $source_id 来源ID
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Chapter extends Model {
	#[Inject]
	protected DataSet $dataSet;
	
	const STATUS_PENDING = 'pending';
	const STATUS_PUBLISH = 'publish';
	
	protected ?string $table = 'chapter';
	
	protected array $casts = [
		'tags' => 'json',
	];
	
	protected array $hidden = [
		'content'
	];
	
	function author(): HasOne {
		return $this->hasOne(Author::class, 'id', 'author_id');
	}
	
	function novel(): HasOne {
		return $this->hasOne(Novel::class, 'id', 'novel_id');
	}
	
	
	function save(array $options = []): bool {
		$tags = $this->dataSet->convertContentToPattern(null, $this->content);
		$this->tags = array_keys(JiebaAnalyse::extractTags(implode(' ', $tags), 30));
		return parent::save($options);
	}
}