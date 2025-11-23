<?php
namespace Modules\NobilikGroupedTags\Listeners;

use App\Conversation; // Используем App\Conversation
use App\Thread;       // Используем App\Thread
use App\User;         // Используем App\User (хотя не используется напрямую в этой логике)
use Module\NobilikGroupedTags\Entities\TagGroup; // Ваша модель группы тегов
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversationListener
{
    /**
     * Копирование тегов из предыдущей беседы при создании новой клиентом.
     * @param object $event Объект события (содержит $event->conversation)
     */
    public function handleMailReceived($event)
    {
        /** @var Conversation $conversation */
        $conversation = $event->conversation;
        
        \Log::emergency('handleMailReceived fired', ['event' => $event]);
        // Находим предыдущую беседу по email клиента
        // TODO: by chat ids?
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

        // Находим группы с флагом 'copy_to_new_conversation'
        $groupsToCopy = TagGroup::where('copy_to_new_conversation', true)->get();
        $tagsToApply = [];

        foreach ($groupsToCopy as $group) {
            // Получаем ID тегов, принадлежащих этой группе
            // Применяем eager loading, чтобы избежать N+1, если Model TagGroup Tag настроена правильно
            $groupTagIds = $group->tags()->pluck('tags.id')->toArray();
            
            // Находим пересечение
            $intersection = array_intersect($previousTagIds, $groupTagIds);
            
            $tagsToApply = array_merge($tagsToApply, $intersection);
        }
        
        // Применяем теги в новую беседу
        if (!empty($tagsToApply)) {
            $conversation->tags()->syncWithoutDetaching(array_unique($tagsToApply));
        }
    }

}