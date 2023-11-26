<?php
namespace App\Model\Scope;

use App\Model\User;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Scope;

class UserScope implements Scope
{
	public function apply(Builder $builder, Model $model): void
	{
		$builder->where('type', User::TYPE_USER);
	}
}