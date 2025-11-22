<?php

namespace Modules\NobilikGroupedTags\Observers;

use App\Conversation;
use Illuminate\Support\Facades\Cache;
use Modules\NobilikGroupedTags\Services\MandatoryTagService;

class ConversationObserver
{
    public function saved(Conversation $conversation)
    {
        $cacheKey = "n_gt_missing_{$conversation->id}";

        $missing = Cache::remember($cacheKey, 5, function () use ($conversation) {
            return app(MandatoryTagService::class)->getMissingGroups($conversation);
        });

        $needs = !empty($missing);

        if ($conversation->needs_tagging !== $needs) {
            $conversation->timestamps = false;
            $conversation->setAttribute('needs_tagging', $needs);
            $conversation->save();
        }
    }
}
