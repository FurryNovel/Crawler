<?php

namespace App\Model;

use App\Model\Model;
use App\Model\Scope\AuthorScope;
use App\Model\Scope\UserScope;
use Carbon\Carbon;
use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;

/**
 * @property string $name
 * @property int $count
 */
class Tag extends Model {
	protected ?string $table = 'tag';
	
	protected string $primaryKey = 'name';
	
	public bool $timestamps = false;
}