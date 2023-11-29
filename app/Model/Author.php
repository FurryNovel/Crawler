<?php

namespace App\Model;

use App\Model\Model;
use App\Model\Scope\AuthorScope;
use App\Model\Scope\UserScope;
use Carbon\Carbon;

/**
 */
class Author extends User {
	protected function bootGlobalScope(): void {
		static::addGlobalScope(new AuthorScope);
	}
}