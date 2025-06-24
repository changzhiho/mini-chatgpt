<?php
// tests/Feature/ChatPageAccessTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

class ChatPageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_chat_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/ask');

        $response->assertStatus(200);

        $response->assertInertia(
            fn(Assert $page) =>
            $page->component('Ask/Index')
                ->has('conversations')
                ->has('models')
        );
    }
}
