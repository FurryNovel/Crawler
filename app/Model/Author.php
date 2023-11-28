<?php

namespace App\Model;

use App\Model\Model;
use App\Model\Scope\AuthorScope;
use App\Model\Scope\UserScope;
use Carbon\Carbon;

/**
 */
class Author extends User {
	protected function boot(): void {
		parent::boot();
		static::addGlobalScope(new AuthorScope);
	}
}