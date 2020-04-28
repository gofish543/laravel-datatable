<?php

namespace Gofish\Datatable\Tests\Unit\Traits;

use Exception;
use Gofish\Datatable\Tests\TestCase;
use Illuminate\Support\Facades\Request;

class SelectableTest extends TestCase
{
	public function testScopeSelectable()
	{
		$this->setProperty($this->profile, 'attributes', [
			'id' => 5,
		]);
		$this->setProperty($this->user, 'attributes', [
			'id' => 1,
			'name' => 'Tester',
		]);
		$this->setProperty($this->user, 'relations', [
			'profile' => $this->profile,
		]);
		$this->user->setHidden([])->guard([]);

		// No params
		Request::replace([]);
		$this->user->newQuery()->selectable();
		$this->assertEquals([
			'id' => 1,
			'name' => 'Tester',
			'profile' => [
				'id' => 5,
			],
		], $this->user->toArray());

		// Assert that fields are selected down
		Request::replace([
			'selectableFields' => 'id, name',
		]);
		$this->user->newQuery()->selectable();
		$this->assertEquals([
			'id' => 1,
			'name' => 'Tester',
		], $this->user->toArray());

		// Assert that relation is selected
		Request::replace([
			'selectableFields' => 'id,profile.id',
		]);
		$this->user->newQuery()->selectable();
		$this->assertEquals([
			'id' => 1,
			'profile' => [
				'id' => 5,
			],
		], $this->user->toArray());

		// Assert that relation is wild carded
		Request::replace([
			'selectableFields' => 'id,profile',
		]);
		$this->user->newQuery()->selectable();
		$this->assertEquals([
			'id' => 1,
			'profile' => [
				'id' => 5,
			],
		], $this->user->toArray());

		try
		{
			// Assert not loaded relation aborts
			Request::replace([
				'selectableFields' => 'posts',
			]);
			$this->user->newQuery()->selectable();
			$this->user->toArray();
			$this->assertFalse(true, 'Abort not fired');
		}
		catch (Exception $exception)
		{
			$this->assertTrue(true, 'Abort fired successfully');
		}

		// Assert invalid field nulls out, on non-relation
		Request::replace([
			'selectableFields' => 'null',
		]);
		$this->user->newQuery()->selectable();
		$this->assertEquals([
			'null' => null,
		], $this->user->toArray());
	}
}