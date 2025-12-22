<?php
namespace Modules\NobilikGroupedTags\Listeners;

use App\Conversation;
use Illuminate\Support\Facades\Log;
use Modules\Tags\Entities\Tag;
use Modules\NobilikGroupedTags\Entities\TagGroup;

class ConversationListener
{
    public function handleMailReceived(Conversation $conversation, $thread, $customer)
    {

        // Ищем предыдущую беседу
        $previousConversation = Conversation::where('customer_email', $conversation->customer_email)
            ->where('id', '<', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$previousConversation) {
            return;
        }

        // Получаем теги предыдущей беседы через модуль Tags
        $previousTags = Tag::conversationTags($previousConversation);
        $previousTagIds = $previousTags->pluck('id')->toArray();

        if (empty($previousTagIds)) {
            return;
        }

        // Ищем группы, которые копируем
        $groupsToCopy = TagGroup::where('copy_to_new_conversation', true)->get();

        $tagsToApply = [];

        foreach ($groupsToCopy as $group) {
            $groupTagIds = $group->tags()->pluck('tags.id')->toArray();
            $intersection = array_intersect($previousTagIds, $groupTagIds);

            $tagsToApply = array_merge($tagsToApply, $intersection);
        }

        $tagsToApply = array_unique($tagsToApply);

        if (empty($tagsToApply)) {
            return;
        }

        // Применяем каждый тег через модуль Tags
        foreach ($tagsToApply as $tagId) {
            try {
                $tag = Tag::find($tagId);
                if ($tag) {
                    $tag->attachToConversation($conversation->id);
                }
            } catch (\Throwable $e) {
                Log::emergency('[GT] ERROR while applying tag', [
                    'tag_id' => $tagId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
