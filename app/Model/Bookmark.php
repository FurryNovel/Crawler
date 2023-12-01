<?php

namespace App\Model;

use App\Model\Model;
use App\Model\Scope\AuthorScope;
use App\Model\Scope\UserScope;
use Carbon\Carbon;
use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;

/**
 * @property int $id
 * @property int $user_id
 * @property int $novel_id
 * @property int $chapter_id
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property Novel $novel
 */
class Bookmark extends Model {
	protected ?string $table = 'bookmark';
	
	
	function novel(): \Hyperf\Database\Model\Relations\HasOne {
		
		return $this->hasOne(Novel::class, 'id', 'novel_id');
	}
	
	function chapter(): \Hyperf\Database\Model\Relations\HasOne {
		return $this->hasOne(Chapter::class, 'id', 'chapter_id');
	}
}