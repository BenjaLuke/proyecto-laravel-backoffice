<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_backoffice_cannot_move_category_under_its_descendant(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'permissions' => User::defaultPermissions(),
        ]);

        $parent = Category::factory()->create([
            'code' => 'PARENT',
            'name' => 'Padre',
        ]);

        $child = Category::factory()->create([
            'code' => 'CHILD',
            'name' => 'Hija',
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($user)->put(route('categories.update', $parent), [
            'code' => 'PARENT',
            'name' => 'Padre',
            'description' => null,
            'parent_id' => $child->id,
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($parent->refresh()->parent_id);
    }

    public function test_api_cannot_move_category_under_its_descendant(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['categories:write']);

        $parent = Category::factory()->create([
            'code' => 'PARENT',
            'name' => 'Padre',
        ]);

        $child = Category::factory()->create([
            'code' => 'CHILD',
            'name' => 'Hija',
            'parent_id' => $parent->id,
        ]);

        $response = $this->putJson("/api/categories/{$parent->id}", [
            'code' => 'PARENT',
            'name' => 'Padre',
            'description' => null,
            'parent_id' => $child->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');

        $this->assertNull($parent->refresh()->parent_id);
    }
}
