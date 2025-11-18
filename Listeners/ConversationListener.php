<?php
namespace NobilikGroupedTags\Listeners;

use App\Conversation; // Используем App\Conversation
use App\Thread;       // Используем App\Thread
use App\User;         // Используем App\User (хотя не используется напрямую в этой логике)
use Module\NobilikGroupedTags\Entities\TagGroup; // Ваша модель группы тегов
use Illuminate\Support\Facades\DB;


class ConversationListener
{
    /**
     * Свойство 5: Копирование тегов из предыдущей беседы при создании новой клиентом.
     * @param object $event Объект события (содержит $event->conversation)
     */
    public function handleMailReceived($event)
    {
        /** @var Conversation $conversation */
        $conversation = $event->conversation;
        
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

    /**
     * Свойство 2 (часть 1): Навешивание тегов, если беседа создана ВРУЧНУЮ СОТРУДНИКОМ.
     * @param Conversation $conversation
     * @param Thread $thread
     */
    public function handleConversationCreatedByUser(Conversation $conversation, Thread $thread)
    {
        // Вызываем новый метод для auto_apply
        $this->applyAutoApplyTags($conversation);
    }

    /**
     * Свойство 2 (часть 2): Навешивание тегов при ПЕРВОМ ДЕЙСТВИИ СОТРУДНИКА (для автоматически созданных бесед).
     * @param Conversation $conversation
     * @param Thread $thread
     */
    public function handleUserAction(Conversation $conversation, Thread $thread)
    {
        // Проверяем, что беседа создана клиентом (автоматически)
        if ($conversation->source_type !== 'user') {
            
            // Проверяем, что это первое действие сотрудника в беседе.
            $userThreadsCount = $conversation->threads()
                ->whereNotNull('user_id')
                ->where('id', '<=', $thread->id) 
                ->count();

            if ($userThreadsCount === 1) {
                // Это первое действие сотрудника (ответ или заметка)
                // Вызываем новый метод для auto_apply
                $this->applyAutoApplyTags($conversation);
            }
        }
    }


    /**
     * Навешивает все теги из групп, где свойство auto_apply = true.
     * Этот метод используется для Свойства 2 (автоматическое навешивание при создании/первом действии сотрудника).
     * @param Conversation $conversation
     */
    protected function applyAutoApplyTags(Conversation $conversation)
    {
        // 1. Получаем ID групп, для которых разрешено АВТОМАТИЧЕСКОЕ ПРИСВОЕНИЕ.
        // КРИТИЧЕСКОЕ ИЗМЕНЕНИЕ: Используем флаг 'auto_apply'
        $group_ids = TagGroup::where('auto_apply', true)
                            ->pluck('id');
        
        if ($group_ids->isEmpty()) {
            return;
        }
        
        // 2. Получаем ID тегов, связанных только с найденными группами.
        $tagsToApply = DB::table('tag_group_tag')
            ->whereIn('tag_group_id', $group_ids)
            ->pluck('tag_id')
            ->unique()
            ->toArray();

        // 3. Применяем теги в беседу.
        if (!empty($tagsToApply)) {
            $conversation->tags()->syncWithoutDetaching($tagsToApply);
        }
    }
    
}