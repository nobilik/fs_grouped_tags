<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagGroupsTables extends Migration
{
    public function up()
    {
        // 1. Таблица групп тегов
        Schema::create('tag_groups', function (Blueprint $table) {
            $table->increments('id'); 
            $table->string('name')->unique();
            $table->integer('max_tags')->default(10)->comment('Свойство 2: не более N тегов');
            $table->boolean('copy_to_new_conversation')->default(false)->comment('Свойство 3: копировать в новую беседу');
            $table->boolean('auto_apply')->default(false);
            $table->timestamps();
        });

        // 2. Таблица-связка (Pivot)
        Schema::create('tag_group_tag', function (Blueprint $table) {
            // FK на ID группы
            $table->unsignedBigInteger('tag_group_id');
            // FK на ID тега из стандартной таблицы FreeScout
            $table->unsignedBigInteger('tag_id'); 

            $table->foreign('tag_group_id')->references('id')->on('tag_groups')->onDelete('cascade');
            // Считаем, что таблица tags существует и имеет поле id
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            
            // Свойство 1: Один тег -> только одна группа. 
            // Это обеспечивается уникальным индексом на tag_id
            $table->unique('tag_id', 'tag_group_tag_tag_id_unique');
            
            // Составной ключ
            $table->primary(['tag_group_id', 'tag_id']); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('tag_group_tag');
        Schema::dropIfExists('tag_groups');
    }
}