<?php

namespace Modules\NobilikGroupedTags\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Tags\Entities\Tag; // <-- Добавлен импорт модели Tags FreeScout!

class TagGroup extends Model
{
    protected $table = 'tag_groups';
    protected $fillable = ['name', 'max_tags', 'copy_to_new_conversation', 'auto_apply'];
    protected $casts = [
        'copy_to_new_conversation' => 'boolean',
        'auto_apply' => 'boolean',
        'max_tags' => 'integer',
    ];

    /**
     * Связь "многие ко многим" с тегами.
     * Используется таблица 'tag_group_tag'.
     */
    public function tags()
    {
        // Tag::class - это стандартная модель тегов FreeScout
        return $this->belongsToMany(Tag::class, 'tag_group_tag', 'tag_group_id', 'tag_id');
    }
    
    // Метод для получения ID тегов, принадлежащих группе
    public function tagIds()
    {
        return $this->tags()->pluck('tag_id')->toArray();
    }
}