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
 * @property string $type 用户类型：TYPE_xxx
 * @property string $name 用户名
 * @property string $password 密码
 * @property string $nickname 昵称
 * @property string $desc 描述
 * @property string $status 状态：STATUS_xxx
 * @property array $ext_data 扩展数据
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class User extends Model implements Authenticatable {
	use AuthAbility;
	
	const TYPE_USER = 'user';
	const TYPE_AUTHOR = 'author';
	const TYPE_ADMIN = 'admin';
	
	
	protected ?string $table = 'user';
	
	protected array $casts = [
		'ext_data' => 'json',
	];
	
	protected static function booted(): void {
		static::addGlobalScope(new UserScope);
	}
	
}