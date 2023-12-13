<?php

namespace App\Model;

use App\Model\Model;
use App\Model\Scope\AuthorScope;
use App\Model\Scope\UserScope;
use Carbon\Carbon;
use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;

class Account extends User implements Authenticatable {
	protected function bootGlobalScope(): void {}
}