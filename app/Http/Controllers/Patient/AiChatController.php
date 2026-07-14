<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiChatController extends Controller
{
    public function index(): View
{
    $user = Auth::user();

    $conversations = $user->aiChatConversations()
        ->latest('last_message_at')
        ->latest('id')
        ->get();

    return view('patient.ai-chat.index', [
        'pageTitle' => 'مساعد اتزان الذكي',
        'activePage' => 'ai-chat',
        'conversations' => $conversations,
        'activeConversation' => null,
        'messages' => collect(),
    ]);
}

    public function show(AiChatConversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $user = Auth::user();

        $conversations = $user->aiChatConversations()
            ->latest('last_message_at')
            ->latest('id')
            ->get();

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get();

        return view('patient.ai-chat.index', [
            'pageTitle' => 'مساعد اتزان الذكي',
            'activePage' => 'ai-chat',
            'conversations' => $conversations,
            'activeConversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, AiChatService $chatService): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $user = Auth::user();

        $conversation = null;

        if (! empty($validated['conversation_id'])) {
            $conversation = AiChatConversation::where('user_id', $user->id)
                ->where('id', $validated['conversation_id'])
                ->firstOrFail();
        }

        if (! $conversation) {
            $conversation = $chatService->createConversation($user, $validated['message']);
        }

        $result = $chatService->sendMessage($conversation, $user, $validated['message']);

        return response()->json([
            'ok' => true,
            'conversation' => [
                'id' => $result['conversation']->id,
                'title' => $result['conversation']->title,
            ],
            'user_message' => [
                'id' => $result['user_message']->id,
                'role' => $result['user_message']->role,
                'content' => $result['user_message']->content,
                'time' => $result['user_message']->created_at?->format('H:i'),
            ],
            'assistant_message' => [
                'id' => $result['assistant_message']->id,
                'role' => $result['assistant_message']->role,
                'content' => $result['assistant_message']->content,
                'time' => $result['assistant_message']->created_at?->format('H:i'),
            ],
        ]);
    }

    public function destroy(AiChatConversation $conversation): RedirectResponse
    {
        $this->authorize('delete', $conversation);

        $conversation->delete();

        return redirect()
            ->route('patient.ai-chat.index')
            ->with('success', 'تم حذف المحادثة بنجاح.');
    }
}
