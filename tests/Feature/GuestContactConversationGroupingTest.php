<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuestContactConversationGroupingTest extends TestCase
{
    use RefreshDatabase;

    private function sendMessage(string $email, string $message): void
    {
        $this->post(route('contact.messages.store'), [
            'guest_name' => 'زائر تجريبي',
            'guest_email' => $email,
            'subject' => 'استفسار',
            'message' => $message,
        ])->assertSessionHasNoErrors();
    }

    public function test_multiple_messages_from_same_email_join_the_same_open_conversation(): void
    {
        $this->sendMessage('same@example.com', 'الرسالة الأولى.');
        $this->sendMessage('same@example.com', 'الرسالة الثانية.');
        $this->sendMessage('same@example.com', 'الرسالة الثالثة.');

        $this->assertSame(1, DB::table('conversations')->where('guest_email', 'same@example.com')->count());

        $conversationId = DB::table('conversations')->where('guest_email', 'same@example.com')->value('id');

        $this->assertSame(3, DB::table('messages')->where('conversation_id', $conversationId)->count());
    }

    public function test_different_emails_get_separate_conversations(): void
    {
        $this->sendMessage('person-a@example.com', 'رسالة من الشخص الأول.');
        $this->sendMessage('person-b@example.com', 'رسالة من الشخص الثاني.');

        $this->assertSame(2, DB::table('conversations')->count());
    }

    public function test_closed_conversation_is_not_reused_a_new_one_is_created(): void
    {
        $this->sendMessage('closed@example.com', 'الرسالة الأولى.');

        DB::table('conversations')->where('guest_email', 'closed@example.com')->update(['status' => 'closed']);

        $this->sendMessage('closed@example.com', 'رسالة بعد ما الأدمن سكر المحادثة.');

        $this->assertSame(2, DB::table('conversations')->where('guest_email', 'closed@example.com')->count());
    }
}
