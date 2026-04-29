<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_abilities_are_limited_to_user_permissions(): void
    {
        $permissions = array_replace(User::defaultPermissions(), [
            'products_view' => true,
            'categories_manage' => true,
        ]);

        $user = User::factory()->create([
            'username' => 'api-user',
            'password' => 'secret-password',
            'permissions' => $permissions,
        ]);

        $response = $this->postJson('/api/tokens', [
            'username' => 'api-user',
            'password' => 'secret-password',
            'device_name' => 'test-client',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token']);

        $abilities = $user->tokens()->firstOrFail()->abilities;

        $this->assertContains('products:read', $abilities);
        $this->assertContains('categories:write', $abilities);
        $this->assertNotContains('products:write', $abilities);
        $this->assertNotContains('calendar:write', $abilities);
    }
}
