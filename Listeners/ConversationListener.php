<?php
namespace Modules\NobilikGroupedTags\Listeners;

use App\Conversation;
use Illuminate\Support\Facades\Log;
use Modules\Tags\Entities\Tag;
use Module\NobilikGroupedTags\Entities\TagGroup;

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

        Log::emergency('[GT] Previous tags', ['tags' => $previousTagIds]);

        if (empty($previousTagIds)) {
            Log::emergency('[GT] Previous conversation has no tags');
            return;
        }

        // Ищем группы, которые копируем
        $groupsToCopy = TagGroup::where('copy_to_new_conversation', true)->get();

        Log::emergency('[GT] Groups with copy flag', [
            'count' => $groupsToCopy->count(),
            'ids' => $groupsToCopy->pluck('id'),
        ]);

        $tagsToApply = [];

        foreach ($groupsToCopy as $group) {
            $groupTagIds = $group->tags()->pluck('tags.id')->toArray();
            $intersection = array_intersect($previousTagIds, $groupTagIds);

            Log::emergency('[GT] Checking group', [
                'group_id' => $group->id,
                'group_tags' => $groupTagIds,
                'intersection' => $intersection,
            ]);

            $tagsToApply = array_merge($tagsToApply, $intersection);
        }

        $tagsToApply = array_unique($tagsToApply);

        Log::emergency('[GT] Tags to apply', ['tags' => $tagsToApply]);

        if (empty($tagsToApply)) {
            Log::emergency('[GT] No tags to apply');
            return;
        }

        // Применяем каждый тег через модуль Tags
        foreach ($tagsToApply as $tagId) {
            try {
                $tag = Tag::find($tagId);
                if ($tag) {
                    $tag->attachToConversation($conversation->id);
                    Log::emergency("[GT] Tag {$tagId} attached");
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
