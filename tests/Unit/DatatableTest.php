<?php

namespace Dykhuizen\Datatable\Tests\Unit;

use Dykhuizen\Datatable\Tests\TestCase;

class DatatableTest extends TestCase
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function testEscapeLike()
	{
		// Test that it generally works
		$actual = $this->callMethod($this->user, 'escapeLike', [
			'%hello%',
		]);
		$this->assertEquals('\%hello\%', $actual);

		// Test escapes on one side
		$actual = $this->callMethod($this->user, 'escapeLike', [
			'%hello',
		]);
		$this->assertEquals('\%hello', $actual);

		// Test custom escape sequence
		$actual = $this->callMethod($this->user, 'escapeLike', [
			'%hello%', '*',
		]);
		$this->assertEquals('*%hello*%', $actual);
	}

	public function testFilterAndExplode()
	{
		// Test method can filter and explode properties
		$actual = $this->callMethod($this->user, 'filterAndExplode', [
			'hello,world',
		]);
		$this->assertEquals([
			'hello',
			'world',
		], $actual);

		// Test that it ignores null values
		$actual = $this->callMethod($this->user, 'filterAndExplode', [
			'hello,,world',
		]);
		$this->assertEquals([
			'hello',
			'world',
		], $actual);

		// Test comma whitespace ignored
		$actual = $this->callMethod($this->user, 'filterAndExplode', [
			'hello, world',
		]);
		$this->assertEquals([
			'hello',
			'world',
		], $actual);

		// Test custom filter function
		$actual = $this->callMethod($this->user, 'filterAndExplode', [
			'hello,world', function($item)
			{
				return $item != 'world';
			},
		]);
		$this->assertEquals([
			'hello',
		], $actual);
	}

	public function testExplodeRelation()
	{
		// Test method can explode on '.'
		$actual = $this->callMethod($this->user, 'explodeRelation', [
			'user.role',
		]);
		$this->assertEquals([
			'user',
			'role',
		], $actual);

		// Test too many '.'
		$actual = $this->callMethod($this->user, 'explodeRelation', [
			'user.role.people',
		]);
		$this->assertEquals([], $actual);

		// Test no '.'
		$actual = $this->callMethod($this->user, 'explodeRelation', [
			'user',
		]);
		$this->assertEquals([], $actual);
	}

	public function testColumnExists()
	{
		// Column should exist based on mocked values
		$actual = $this->callMethod($this->user, 'columnExists', [
			$this->user, 'username',
		]);
		$this->assertTrue($actual);

		// Column should not exist
		$actual = $this->callMethod($this->user, 'columnExists', [
			$this->user, 'null',
		]);
		$this->assertFalse($actual);
	}

	public function testQueryJoinBuilder()
	{
		// Left join profile query
		$query = $this->user->newQuery()->with(['profile']);
		$relation = $query->getRelation('profile');
		$joinedId = $this->callMethod($this->user, 'queryJoinBuilder', [$query, $relation]);
		$actualQuery = $query;
		$expectedQuery = $this->user->newQuery()->leftJoin("profiles as {$joinedId}", 'users.id', '=', "{$joinedId}.user_id");
		$this->assertEquals($expectedQuery->toSql(), $actualQuery->toSql());

		// Left join user query
		$query = $this->profile->newQuery()->with(['user']);
		$relation = $query->getRelation('user');
        $joinedId = $this->callMethod($this->user, 'queryJoinBuilder', [$query, $relation]);
        $actualQuery = $query;
		$expectedQuery = $this->profile->newQuery()->leftJoin("users as {$joinedId}", 'profiles.user_id', '=', "{$joinedId}.id");
		$this->assertEquals($expectedQuery->toSql(), $actualQuery->toSql());

		// Left join with parent
		$query = $this->comment->newQuery()->with(['parent']);
		$relation = $query->getRelation('parent');
        $joinedId = $this->callMethod($this->comment, 'queryJoinBuilder', [$query, $relation]);
        $actualQuery = $query;
		$expectedQuery = $this->comment->newQuery()->from('comments as parent_comments')
			->leftJoin("comments as {$joinedId}", 'parent_comments.parent_id', '=', "{$joinedId}.id");
		$this->assertEquals($expectedQuery->toSql(), $actualQuery->toSql());

		// Left join on invalid relation class
		$query = $this->user->newQuery()->with(['posts']);
		$relation = $query->getRelation('posts');
        $joinedId = $this->callMethod($this->user, 'queryJoinBuilder', [$query, $relation]);
        $actualQuery = $query;
		$expectedQuery = $this->user->newQuery()->whereRaw('0 = 1');
		$this->assertEquals($expectedQuery->toSql(), $actualQuery->toSql());
	}

	public function testHasRelation()
	{
		// Relation exists
		$actual = $this->callMethod($this->user, 'hasRelation', [
			'posts',
		]);
		$this->assertTrue($actual);

		// Relation exists and is loaded short cut
		$this->setProperty($this->user, 'relations', [
			'hello_world' => true,
		]);
		$actual = $this->callMethod($this->user, 'hasRelation', [
			'hello_world',
		]);
		$this->assertTrue($actual);

		// Relation does not exist
		$actual = $this->callMethod($this->user, 'hasRelation', [
			'not_a_relation',
		]);
		$this->assertFalse($actual);

		// Relation does exist on passed model
		$actual = $this->callMethod($this->user, 'hasRelation', [
			'user', $this->profile,
		]);
		$this->assertTrue($actual);
	}

	public function testNullQuery()
	{
		$actualQuery = $this->callMethod($this->user, 'nullQuery', [
			$this->user->newQuery(),
		]);
		$expectedQuery = $this->user->newQuery()->select('*')->whereRaw('0 = 1');
		$this->assertEquals($expectedQuery->toSql(), $actualQuery->toSql());

	}
}
