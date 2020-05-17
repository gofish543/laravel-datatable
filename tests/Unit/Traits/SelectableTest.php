<?php

namespace Dykhuizen\Datatable\Tests\Unit\Traits;

use Dykhuizen\Datatable\Tests\TestCase;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Request;

class SelectableTest extends TestCase {

    public function testScopeSelectable() {
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

        try {
            // Assert not loaded relation aborts
            Request::replace([
                'selectableFields' => 'posts',
            ]);
            $this->user->newQuery()->selectable();
            $this->user->toArray();
            $this->assertFalse(true, 'Abort not fired');
        } catch (Exception $exception) {
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

    public function testSelectableHiddenFieldsRemoved() {
        $this->setProperty($this->profile, 'attributes', [
            'id' => 5,
            'password' => '12345',
        ]);
        $this->setProperty($this->comment, 'attributes', [
            'id' => 4,
            'password' => '54321',
        ]);

        $this->setProperty($this->user, 'attributes', [
            'id' => 1,
            'name' => 'Tester',
            'password' => 'giraffe',
        ]);
        $this->setProperty($this->user, 'relations', [
            'profile' => $this->profile,
            'comments' => new Collection([$this->comment])
        ]);

        $this->user->setHidden(['password']);
        $this->profile->setHidden(['password']);
        $this->comment->setHidden(['password']);

        // Assert that relation is selected
        Request::replace([
            'selectableFields' => implode(',', [
                'id', 'name', 'password',
                'profile.id', 'profile.password',
                'comments',
            ])
        ]);
        $this->user->newQuery()->selectable();

        $toArray = $this->user->toArray();

        $this->assertArrayHasKey('profile', $toArray);
        $this->assertArrayHasKey('comments', $toArray);

        $this->assertArrayNotHasKey('password', $toArray);
        $this->assertArrayNotHasKey('password', $toArray['profile']);
        $this->assertArrayNotHasKey('password', $toArray['comments'][0]);
    }
}