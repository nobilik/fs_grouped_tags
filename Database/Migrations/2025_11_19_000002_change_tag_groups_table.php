<?php
/**
 * Изменение полей в таблице tag_groups: 
 * - Удаление auto_apply.
 * - Добавление max_tags_for_conversation (ограничение).
 * - Добавление required_for_conversation (обязательность).
 */
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTagGroupsTable extends Migration
{
    /**
     * Запуск миграций.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tag_groups', function (Blueprint $table) {
            // 1. Удаляем устаревшее поле
            if (Schema::hasColumn('tag_groups', 'auto_apply')) {
                $table->dropColumn('auto_apply');
            }
            
            // 2. Добавляем ограничение на количество тегов из группы для одной заявки
            $table->integer('max_tags_for_conversation')->default(0)
                  ->after('mailbox_id')
                  ->comment('Макс. кол-во тегов из группы, которое можно присвоить заявке. 0 = без ограничения.');
            
            // 3. Добавляем флаг обязательности
            $table->boolean('required_for_conversation')->default(false)
                  ->after('max_tags_for_conversation')
                  ->comment('Если true, заявка должна иметь хотя бы один тег из этой группы.');
        });
    }

    /**
     * Откат миграций.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tag_groups', function (Blueprint $table) {
            // $table->boolean('auto_apply')->default(false); // восстановление не требуется, т.к. не использовалось
            if (Schema::hasColumn('tag_groups', 'max_tags_for_conversation')) {
                $table->dropColumn('max_tags_for_conversation');
            }
            if (Schema::hasColumn('tag_groups', 'required_for_conversation')) {
                $table->dropColumn('required_for_conversation');
            }
        });
    }
}