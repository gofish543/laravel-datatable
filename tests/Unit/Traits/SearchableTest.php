<?php

namespace Gofish\Datatable\Tests\Unit\Traits;

use Gofish\Datatable\Tests\TestCase;
use Illuminate\Support\Facades\Request;

class SearchableTest extends TestCase
{
	public function testScopeSearchable()
	{
		// No params
		Request::replace([]);
		$query = $this->user->newQuery()->searchable();
		$this->assertEquals('select * from `users`', $query->toSql());

		// Assert that the base where is applied
		Request::replace([
			'searchColumns' => 'id, name',
			'search' => '1',
		]);
		$query = $this->user->newQuery()->searchable();
		$this->assertStringContainsString("(`{$this->user->getTable()}`.`id` LIKE ?) or (`{$this->user->getTable()}`.`name` LIKE ?)", $query->toSql());

		// Assert that a relation where is applied
		Request::replace([
			'searchColumns' => 'profile.id',
			'search' => '1',
		]);
		$query = $this->user->newQuery()->searchable();
		$this->assertStringContainsString("(`{$this->profile->getTable()}`.`id` LIKE ?)", $query->toSql());

		// Assert that the Searchable function was called
		Request::replace([
			'searchColumns' => 'address',
			'search' => 'asc',
		]);
		$query = $this->user->newQuery()->searchable();
		$this->assertStringContainsString("`address` = ?", $query->toSql());

		// Assert that dates true/false handled
		Request::replace([
			'searchColumns' => 'deleted_at',
			'search' => 'false',
		]);
		$query = $this->user->newQuery()->searchable();
		$this->assertStringContainsString("(`{$this->user->getTable()}`.`deleted_at` is null)", $query->toSql());

		Request::replace([
			'searchColumns' => 'deleted_at',
			'search' => 'true',
		]);
		$query = $this->user->newQuery()->searchable();
		$this->assertStringContainsString("(`{$this->user->getTable()}`.`deleted_at` is not null)", $query->toSql());

		Request::replace([
			'searchColumns' => 'deleted_at',
			'search' => 'dateformat',
		]);
		$query = $this->user->newQuery()->searchable();
		$this->assertStringContainsString("(date(`{$this->user->getTable()}`.`deleted_at`) = ?)", $query->toSql());

		// Assert that invalid column drops null query
		Request::replace([
			'searchColumns' => 'null',
			'search' => 'asc',
		]);
		$query = $this->user->newQuery()->searchable();
		$this->assertNullQueryApplied($query);
	}
}