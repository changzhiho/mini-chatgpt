<?php
// tests/Feature/ConversationCreationTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversationCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_conversation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/ask/new', [
            'model' => 'gpt-4'
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'model' => 'gpt-4',
            'title' => 'Nouvelle conversation'
        ]);
    }
}
