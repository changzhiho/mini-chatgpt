<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ConversationService;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_conversation()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $service = new ConversationService();

        // Act
        $conversation = $service->createConversation('gpt-4');

        // Assert
        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals('Nouvelle conversation', $conversation->title);
        $this->assertEquals('gpt-4', $conversation->model);
        $this->assertEquals($user->id, $conversation->user_id);
    }
}
