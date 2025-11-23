<?php

namespace Modules\NobilikGroupedTags\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\NobilikGroupedTags\Entities\TagGroup;
use Modules\NobilikGroupedTags\Entities\TagGroupTag;
use Modules\Tags\Entities\Tag; // Используем модель тегов из модуля Tags
use Modules\Tags\Entities\ConversationTag;
use Illuminate\Support\Facades\DB;
use App\Conversation;

use Modules\NobilikGroupedTags\Services\MandatoryTagService;


class GroupedTagsController extends Controller
{
    /**
     * Отображает главную страницу настроек, список групп и доступные теги.
     * Реализует: Просмотр администратором (Свойство 6).
     */
    public function index()
    {
        // 1. Получаем все группы с их привязанными тегами
        $groups = TagGroup::with('tags')->get();
        
        // 2. Получаем ID всех тегов, которые уже привязаны к какой-либо группе (для фильтрации)
        $assigned_tag_ids = TagGroupTag::pluck('tag_id')->toArray();
        
        // 3. Получаем теги, которые еще не принадлежат ни одной группе
        $available_tags = Tag::whereNotIn('id', $assigned_tag_ids)
            ->orderBy('name')
            ->get();

        return view('nobilikgroupedtags::settings', compact('groups', 'available_tags'));
    }

    /**
     * Создает новую группу тегов.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'copy_to_new_conversation' => 'sometimes|boolean',
            'required_for_conversation' => 'sometimes|boolean', 
            'max_tags_for_conversation' => 'integer',
            'max_tags' => 'required|integer|min:1'
        ]);

        TagGroup::create($request->all());

        return back()->with('success', __('Group created successfully.'));
    }

    /**
     * Обновляет существующую группу тегов.
     * Реализует: Проверка лимита N тегов при его уменьшении (Свойство 2).
     */
    public function update(Request $request, $group_id)
    {
        $group = TagGroup::find($group_id);
        $request->validate([
            'name' => 'required|max:255|unique:tag_groups,name,' . $group->id,
            'copy_to_new_conversation' => 'sometimes|boolean',
            'required_for_conversation' => 'sometimes|boolean', 
            'max_tags_for_conversation' => 'integer',
            'max_tags' => 'required|integer|min:1'
        ]);

        // --- Проверка Свойства 2: Нельзя уменьшить лимит ниже текущего количества тегов ---
        $currentTagCount = $group->tags()->count();
        if ((int)$request->max_tags < $currentTagCount) {
            return back()->with('error', 
                __('Cannot reduce maximum tag limit below the current number of attached tags (:count).', ['count' => $currentTagCount])
            );
        }

        $group->name = $request->input('name');
        $group->max_tags_for_conversation = $request->input('max_tags_for_conversation');
        $group->max_tags = $request->input('max_tags');
        // Обработка чекбокса: если не передан, то false
        $group->copy_to_new_conversation = $request->has('copy_to_new_conversation'); 
        $group->required_for_conversation = $request->has('required_for_conversation'); 

        $group->save();

        return back()->with('success', __('Group updated successfully.'));
    }

    /**
     * Удаляет группу тегов.
     * ИСПРАВЛЕНИЕ: Переход на явный ID ($groupId) и возврат Response::json.
     */
    public function destroy($group_id)
    {
        try {
            DB::beginTransaction();

            $group = TagGroup::findOrFail($group_id);

            // Удаляем все связи тегов с этой группой $group->tags()::detach() 
            $group->tags()->detach();

            // Удаляем саму группу
            $group->delete();

            DB::commit();

            $response = [
                'status'  => 'success',
                'message' => __('Tag group deleted successfully.'),
            ];
            
            return \Response::json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            $response = [
                'status'  => 'error',
                'message' => __('Failed to delete tag group.') . ' ' . $e->getMessage(),
            ];
            return \Response::json($response, 500);
        }
    }

    public function attachTag(Request $request)
    {
        // 1. ВАЛИДАЦИЯ
        $request->validate([
            'group_id' => 'required|exists:tag_groups,id', 
            'tag_id' => 'required|exists:tags,id',
        ]);

        $groupId = $request->input('group_id');
        $tagId = $request->input('tag_id');
        
        $group = TagGroup::findOrFail($groupId);

        // --- 2. Один тег может принадлежать только к одной группе ---
        if (TagGroupTag::where('tag_id', $tagId)->exists()) {
             $error_message = __('Error: This tag already belongs to another group.');
             // Возвращаем ошибку в JSON-формате
             return \Response::json(['status' => 'error', 'message' => $error_message], 403);
        }

        // --- 3. Проверка лимит N тегов ---
        if ($group->tags()->count() >= $group->max_tags) {
            $error_message = __('Error: Group ":name" has reached its limit of :max_tags tags.', [
                'name' => $group->name, 
                'max_tags' => $group->max_tags
            ]);
            // Возвращаем ошибку в JSON-формате
            return \Response::json(['status' => 'error', 'message' => $error_message], 403);
        }

        // 4. ПРИВЯЗКА ТЕГА
        try {
            $group->tags()->attach($tagId);
            
            // 5. УСПЕШНЫЙ ОТВЕТ (JSON)
            return \Response::json([
                'status' => 'success', 
                'message' => __('Tag successfully added to the group.')
            ]);

        } catch (\Exception $e) {
            // Обработка непредвиденных ошибок базы данных
            return Response::json([
                'status' => 'error', 
                'message' => __('Database error during tag attachment.') . ' ' . $e->getMessage()
            ], 500);
        }
    }

   /**
     * Отвязывает тег от группы.
     */
    public function detachTag(Request $request)
    {
        // 1. ВАЛИДАЦИЯ
        $request->validate([
            'group_id' => 'required|exists:tag_groups,id', // Используем "tag_groups"
            'tag_id' => 'required|exists:tags,id',
        ]);

        $groupId = $request->input('group_id');
        $tagId = $request->input('tag_id');
        
        $group = TagGroup::findOrFail($groupId);

        // --- 2. Проверка: Принадлежит ли тег вообще этой группе? ---
        if (!$group->tags()->where('tag_id', $tagId)->exists()) {
             $error_message = __('Error: The tag is not currently assigned to this group.');
             return \Response::json(['status' => 'error', 'message' => $error_message], 404);
        }

        // 3. ОТВЯЗКА ТЕГА
        try {
            $group->tags()->detach($tagId);
            
            // 4. УСПЕШНЫЙ ОТВЕТ (JSON)
            return \Response::json([
                'status' => 'success', 
                'message' => __('Tag successfully detached from the group.')
            ]);

        } catch (\Exception $e) {
            // Обработка непредвиденных ошибок базы данных
            return \Response::json([
                'status' => 'error', 
                'message' => __('Database error during tag detachment.') . ' ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Создает новый тег (не привязанный к группе).
     * Используется для добавления свободных тегов через форму.
     */
    public function storeTag(Request $request)
    {
        $request->validate([
            'tag_name' => 'required|string|max:255',
            // ИСПРАВЛЕНИЕ: Проверка, что это целое число в диапазоне 0-11
            'tag_color' => 'required|integer|min:0|max:11', 
        ]);

        try {
            // Предполагаем, что App\Models\Tag - это модель тега FreeScout
            Tag::create([
                'name' => $request->input('tag_name'),
                'color' => (int)$request->input('tag_color'), // Сохраняем целое число
            ]);

            return redirect()->route('grouped-tags.settings')->with('success', __('Tag successfully created.'));

        } catch (\Exception $e) {
            return redirect()->route('grouped-tags.settings')->with('error', 
                __('Failed to create tag.') . ' ' . $e->getMessage()
            );
        }
    }

    /**
     * Проверяет, выполнены ли условия обязательных тегов для заявки.
     * Вызывается через AJAX перед отправкой ответа.
     */

    public function check(Request $request, MandatoryTagService $service)
    {
        $conversation = Conversation::findOrFail($request->conversation_id);

        $missing = $service->getMissingGroups($conversation);

        return response()->json([
            'status' => $missing ? 'error' : 'success',
            'missing_groups' => $missing
        ]);
    }

    /**
     * Прикрепляет выбранные теги к беседе.
     * Используется метод attachToConversation модели Tag.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachTagToConversation(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required',
            'tag_ids'         => 'required|array|min:1',
            'tag_ids.*'       => 'integer',
            'group_id'        => 'required|integer',
        ]);

        // conversation_id может приходить как массив
        $conversationId = $request->conversation_id;
        if (is_array($conversationId)) {
            $conversationId = (int) $conversationId[0];
        } else {
            $conversationId = (int) $conversationId;
        }

        $groupId = (int) $request->group_id;
        $tagIds  = $request->tag_ids;

        // 1. Проверяем группу
        $group = TagGroup::find($groupId);
        if (!$group) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tag group not found.'
            ], 422);
        }

        $limit = $group->max_tags_for_conversation ?? 0;

        // Получаем все теги группы
        $groupTagIds = TagGroupTag::where('tag_group_id', $groupId)
            ->pluck('tag_id')
            ->toArray();

        // 2. Проверяем, что все теги принадлежат группе
        foreach ($tagIds as $tagId) {
            if (!in_array($tagId, $groupTagIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tag ID {$tagId} does not belong to this group."
                ], 422);
            }
        }

        // 3. Проверяем лимит для группы
        if ($limit > 0) {
            $existingCount = ConversationTag::where('conversation_id', $conversationId)
                ->whereIn('tag_id', $groupTagIds)
                ->count();

            if (($existingCount + count($tagIds)) > $limit) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Maximum {$limit} tags allowed in this group."
                ], 422);
            }
        }

        // 4. Прикрепляем теги
        foreach ($tagIds as $tagId) {
            $tag = Tag::find($tagId);
            if ($tag) {
                $tag->attachToConversation($conversationId);
            }
        }

        return response()->json([
            'status'  => 'ok',
            'message' => 'Tags attached successfully.',
        ]);
    }

    public function checkTagDelete(Request $request)
    {
        $tagId = $request->tag_id;

        // Проверяем — принадлежит ли тег обязательной группе
        $group = GroupedTag::where('tag_id', $tagId)
            ->where('required_for_conversation', true)
            ->first();

        if ($group) {
            return response()->json([
                'allowed' => false,
                'message' => 'Этот тег принадлежит обязательной группе. Удалите его из группы прежде чем удалять тег.'
            ]);
        }

        return response()->json(['allowed' => true]);
    }

}

