<?php

namespace Gofish\Datatable\Tests\Unit\Traits;

use Gofish\Datatable\Tests\TestCase;
use Illuminate\Support\Facades\Request;

class SortableTest extends TestCase
{
	public function testScopeSortable()
	{
		// No params
		Request::replace([]);
		$query = $this->user->newQuery()->sortable();
		$this->assertEquals('select * from `users`', $query->toSql());

		// Mismatch of column to order count
		Request::replace([
			'sortColumns' => 'id, name',
			'sortOrder' => 'asc',
		]);
		$query = $this->user->newQuery()->sortable();
		$this->assertNullQueryApplied($query);

		// Assert that the base order by is applied
		Request::replace([
			'sortColumns' => 'id',
			'sortOrder' => 'asc',
		]);
		$query = $this->user->newQuery()->sortable();
		$this->assertStringContainsString("order by `{$this->user->getTable()}`.`id` asc", $query->toSql());

		// Assert that a relation order by is applied
		Request::replace([
			'sortColumns' => 'profile.id',
			'sortOrder' => 'asc',
		]);
		$query = $this->user->newQuery()->sortable();
		$this->assertStringContainsString("order by `{$this->profile->getTable()}`.`id` asc", $query->toSql());

		// Assert that the Sortable function was called
		Request::replace([
			'sortColumns' => 'address',
			'sortOrder' => 'asc',
		]);
		$query = $this->user->newQuery()->sortable();
		$this->assertStringContainsString("order by `address` asc", $query->toSql());

		// Assert that invalid column drops null query
		Request::replace([
			'sortColumns' => 'null',
			'sortOrder' => 'asc',
		]);
		$query = $this->user->newQuery()->sortable();
		$this->assertNullQueryApplied($query);
	}
}