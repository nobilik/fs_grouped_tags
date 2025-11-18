<?php

namespace Modules\NobilikGroupedTags\Entities;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TagGroupTag extends Pivot
{
    protected $table = 'tag_group_tag';
    
    // Указываем составные ключи
    protected $primaryKey = ['tag_group_id', 'tag_id']; 
    
    public $incrementing = false; // Нет автоинкрементного ID
}