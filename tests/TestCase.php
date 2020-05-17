<?php

namespace Dykhuizen\Datatable\Tests;

use Dykhuizen\Datatable\Datatable;
use Dykhuizen\Datatable\DatatableServiceProvider;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Arr;
use Orchestra\Testbench\TestCase as BaseTestCase;
use ReflectionClass;
use ReflectionException;

class TestCase extends BaseTestCase
{
	/** @var \Dykhuizen\Datatable\Tests\User */
	protected $user;

	/** @var \Dykhuizen\Datatable\Tests\Profile */
	protected $profile;

	/** @var \Dykhuizen\Datatable\Tests\Comment */
	protected $comment;

	/** @var \Dykhuizen\Datatable\Tests\Post */
	protected $post;

	public function setUp(): void
	{
		parent::setUp();
		app()->register(DatatableServiceProvider::class);

		$this->mockSchema();

		$this->user = new User();
		$this->profile = new Profile();
		$this->comment = new Comment();
		$this->post = new Post();
	}

	public function tearDown(): void {
        parent::tearDown();

        User::$selectableFields = [];
        Comment::$selectableFields = [];
        Post::$selectableFields = [];
    }

    /**
	 * Mock the schema hasColumn function
	 *
	 * @return void
	 */
	protected function mockSchema()
	{
		$builder = $this->createPartialMock(Builder::class, [
			'hasColumn',
		]);
		$builder->expects($this->any())
			->method('hasColumn')
			->willReturnCallback(function($table, $column)
			{
				return $column == 'null' ? false : true;
			});

		$databaseConnection = $this->createPartialMock(Connection::class, [
			'getSchemaBuilder',
		]);
		$databaseConnection->expects($this->any())
			->method('getSchemaBuilder')
			->willReturn($builder);

		$databaseManager = $this->createPartialMock(DatabaseManager::class, [
			'connection',
		]);
		$databaseManager->expects($this->any())
			->method('connection')
			->willReturn($databaseConnection);

		$this->app['db'] = $databaseManager;
	}

	/**
	 * Assert that the null query was applied
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $actual
	 *
	 * @return $this
	 */
	protected function assertNullQueryApplied($actual)
	{
		$this->assertStringContainsString('0 = 1', $actual->toSql());

		return $this;
	}

	/**
	 * Call a protected or private method
	 *
	 * @param object $obj
	 * @param string $name
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function callMethod($obj, $name, array $args)
	{
		try
		{
			$class = new ReflectionClass($obj);
			$method = $class->getMethod($name);
			$method->setAccessible(true);
			return $method->invokeArgs($obj, $args);
		}
		catch (ReflectionException $e)
		{
			return null;
		}
	}

	/**
	 * Set a protected or private method
	 *
	 * @param object $obj
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function setProperty($obj, $name, $value)
	{
		try
		{
			$class = new ReflectionClass($obj);
			$property = $class->getProperty($name);
			$property->setAccessible(true);
			$property->setValue($obj, $value);

			return $obj;
		}
		catch (ReflectionException $e)
		{
			return null;
		}
	}
}

class User extends Model
{
	use Datatable;

	/** @var string[] */
	protected $dates = [
		'deleted_at',
	];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function profile()
	{
		return $this->hasOne(Profile::class, 'user_id', 'id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function posts()
	{
		return $this->hasMany(Post::class, 'user_id', 'id');
	}

	public function addressSortable($query, $direction)
	{
		return $query->join('profiles', 'users.id', '=', 'profiles.user_id')->orderBy('address', $direction)->select('users.*');
	}

	public function addressSearchable($query, $search)
	{
		return $query->whereHas('profile', function($query) use ($search)
		{
			return $query->where('address', '=', $search);
		});
	}

	public function addressFilterable($query, $filters)
	{
		return $query->whereHas('profile', function($query) use ($filters)
		{
			return $query->where('address', '=', Arr::first($filters));
		});
	}
}

class Profile extends Model
{
	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function compositeSortable($query, $direction)
	{
		return $query->orderBy('phone', $direction)->orderBy('address', $direction);
	}
}

class Comment extends Model
{
	use Datatable;

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function parent()
	{
		return $this->belongsTo(Comment::class, 'parent_id');
	}
}

class Post extends Model
{
	use Datatable;
}