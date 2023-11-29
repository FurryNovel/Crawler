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
	
	const STATUS_PENDING = 'pending';
	const STATUS_PUBLISH = 'publish';
	
	const TYPE_USER = 'user';
	const TYPE_AUTHOR = 'author';
	const TYPE_ADMIN = 'admin';
	
	
	protected ?string $table = 'user';
	
	protected array $casts = [
		'ext_data' => 'json',
	];
	
	protected array $hidden = [
		'password',
		'ext_data',
	];
	
	protected function bootGlobalScope(): void {
		static::addGlobalScope(new UserScope);
	}
	
	function checkPassword(string $password): bool {
		return password_verify($password, $this->password);
	}
	
	function changePassword(string $password): void {
		$this->password = password_hash($password, PASSWORD_DEFAULT);
		$this->save();
	}
	
	static function login(string $username, string $password): ?User {
		$user = User::query()->where('name', $username)->first();
		if (!$user or !password_verify($password, $user->password)) {
			return null;
		}
		return $user;
	}
	
	static function register(
		string $type,
		string $name,
		string $password,
		array  $qa,
	): static {
		$user = new static([
			'type' => $type,
			'name' => $name,
			'nickname' => $name,
			'password' => password_hash($password, PASSWORD_DEFAULT),
			'desc' => '该用户很懒，什么都没留下',
			'status' => self::STATUS_PUBLISH,
			'ext_data' => [
				'qa' => $qa,
			],
		]);
		$user->save();
		return $user;
	}
}