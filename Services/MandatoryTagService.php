<?php

namespace Modules\NobilikGroupedTags\Services;

use Modules\NobilikGroupedTags\Entities\TagGroupTag;
use Modules\NobilikGroupedTags\Entities\TagGroup;
use Modules\Tags\Entities\ConversationTag;

use Illuminate\Support\Facades\DB;

class MandatoryTagService
{
    public function getMissingGroups($conversation): array
    {
        $mandatory = DB::table('tag_groups')
            ->where('required_for_conversation', 1)
            ->get();

        $tagsInConv = DB::table('conversation_tag')
            ->where('conversation_id', $conversation->id)
            ->pluck('tag_id')
            ->toArray();

        $missing = [];

        foreach ($mandatory as $group) {
            $groupTags = DB::table('tag_group_tag')
                ->where('tag_group_id', $group->id)
                ->pluck('tag_id')
                ->toArray();

            if (!array_intersect($tagsInConv, $groupTags)) {
                $missing[] = [
                    'id'   => $group->id,
                    'name' => $group->name,
                    'tags' => DB::table('tags')->whereIn('id', $groupTags)->get(),
                    'max_tags_for_conversation' => $group->max_tags_for_conversation,
                ];
            }
        }

        return $missing;
    }


    public function countGroupTagsOnConversation(int $conversationId, int $groupId): int
    {
        // 1. Находим группу
        $group = TagGroup::find($groupId);
        if (!$group) {
            return 0;
        }

        // 2. Получаем все id тегов, которые относятся к группе
        $tagIdsInGroup = TagGroupTag::where('tag_group_id', $groupId)
            ->pluck('tag_id')
            ->toArray();

        if (empty($tagIdsInGroup)) {
            return 0;
        }

        // 3. Считаем, сколько из этих тегов установлено в беседе
        return ConversationTag::where('conversation_id', $conversationId)
            ->whereIn('tag_id', $tagIdsInGroup)
            ->count();
    }

}
