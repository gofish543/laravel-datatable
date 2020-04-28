<?php

namespace Dykhuizen\Datatable\Tests\Unit\Traits;

use Dykhuizen\Datatable\Tests\TestCase;
use Illuminate\Support\Facades\Request;

class FilterableTest extends TestCase
{
	public function testScopeFilterable()
	{
		// No params
		Request::replace([]);
		$query = $this->user->newQuery()->filterable();
		$this->assertEquals('select * from `users`', $query->toSql());

		// Assert fails with empty field
		Request::replace([
			'filter' => 'status',
			'filter_status' => '',
		]);
		$query = $this->user->newQuery()->filterable();
		$this->assertNullQueryApplied($query);

		// Assert fails with empty field
		Request::replace([
			'filter' => 'status',
			'filter_status' => 'yes',
		]);
		$query = $this->user->newQuery()->filterable();
		$this->assertStringContainsString("(`{$this->user->getTable()}`.`status` = ?)", $query->toSql());

		// Assert that a relation where is applied
		Request::replace([
			'filter' => 'profile.status',
			'filter_profile.status' => 'no',
		]);
		$query = $this->user->newQuery()->filterable();
		$this->assertStringContainsString("(`{$this->profile->getTable()}`.`status` = ?)", $query->toSql());

		// Assert that the filterable function was called
		Request::replace([
			'filter' => 'address',
			'filter_address' => '12345',
		]);
		$query = $this->user->newQuery()->filterable();
		$this->assertStringContainsString("`address` = ?", $query->toSql());

		// Assert that dates true/false handled
		Request::replace([
			'filter' => 'deleted_at',
			'filter_deleted_at' => 'false',
		]);
		$query = $this->user->newQuery()->filterable();
		$this->assertStringContainsString("(`{$this->user->getTable()}`.`deleted_at` is null)", $query->toSql());

		Request::replace([
			'filter' => 'deleted_at',
			'filter_deleted_at' => 'true',
		]);
		$query = $this->user->newQuery()->filterable();
		$this->assertStringContainsString("(`{$this->user->getTable()}`.`deleted_at` is not null)", $query->toSql());

		Request::replace([
			'filter' => 'deleted_at',
			'filter_deleted_at' => 'dateformat',
		]);
		$query = $this->user->newQuery()->filterable();
		$this->assertStringContainsString("(date(`{$this->user->getTable()}`.`deleted_at`) = ?)", $query->toSql());
	}
}