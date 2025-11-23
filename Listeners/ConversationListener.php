<?php
namespace Modules\NobilikGroupedTags\Listeners;

use App\Conversation;
use Module\NobilikGroupedTags\Entities\TagGroup;

class ConversationListener
{
    /**
     * Копирование тегов из предыдущей беседы при создании новой клиентом.
     *
     * @param Conversation $conversation
     * @param \App\Thread $thread
     * @param \App\User|mixed $customer
     */
    public function handleMailReceived(Conversation $conversation, $thread, $customer)
    {
        // Находим предыдущую беседу по email клиента
        $previousConversation = Conversation::where('customer_email', $conversation->customer_email)
            ->where('id', '<', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$previousConversation) {
            return;
        }

        $previousTagIds = $previousConversation->tags()->pluck('tags.id')->toArray();
        if (empty($previousTagIds)) {
            return;
        }

        $groupsToCopy = TagGroup::where('copy_to_new_conversation', true)->get();
        $tagsToApply = [];

        foreach ($groupsToCopy as $group) {
            $groupTagIds = $group->tags()->pluck('tags.id')->toArray();
            $tagsToApply = array_merge($tagsToApply, array_intersect($previousTagIds, $groupTagIds));
        }

        if (!empty($tagsToApply)) {
            $conversation->tags()->syncWithoutDetaching(array_unique($tagsToApply));
        }
    }
}