<?php
namespace Modules\NobilikGroupedTags\Listeners;

use App\Conversation;
use Module\NobilikGroupedTags\Entities\TagGroup;
use Illuminate\Support\Facades\Log;

class ConversationListener
{
    public function handleMailReceived(Conversation $conversation, $thread, $customer)
    {
        Log::emergency('[GT] Listener START', [
            'conversation_id' => $conversation->id,
            'email' => $conversation->customer_email,
        ]);

        // Находим предыдущую беседу
        $previousConversation = Conversation::where('customer_email', $conversation->customer_email)
            ->where('id', '<', $conversation->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$previousConversation) {
            Log::emergency('[GT] No previous conversation found');
            return;
        }

        Log::emergency('[GT] Previous conversation found', [
            'previous_id' => $previousConversation->id
        ]);

        $previousTagIds = $previousConversation->tags()->pluck('tags.id')->toArray();
        Log::emergency('[GT] Previous tags', ['tags' => $previousTagIds]);

        if (empty($previousTagIds)) {
            Log::emergency('[GT] Previous conversation has no tags');
            return;
        }

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

        Log::emergency('[GT] Tags to apply', ['tags' => $tagsToApply]);

        if (!empty($tagsToApply)) {
            $uniqueTags = array_unique($tagsToApply);
            $conversation->tags()->syncWithoutDetaching($uniqueTags);

            Log::emergency('[GT] Tags applied', ['tags' => $uniqueTags]);
        } else {
            Log::emergency('[GT] No tags to apply');
        }
    }
}
