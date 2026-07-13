<?php

namespace App\Mail;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $guestName;
    public string $replyBody;
    public ?Conversation $conversation;

    public function __construct(string $guestName, string $replyBody, ?Conversation $conversation = null)
    {
        $this->guestName = $guestName;
        $this->replyBody = $replyBody;
        $this->conversation = $conversation;
    }

    public function build()
    {
        return $this
            ->subject('رد من فريق دعم اتزان')
            ->view('emails.support-reply');
    }
}
