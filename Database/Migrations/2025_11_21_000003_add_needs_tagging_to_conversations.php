<!-- add_needs_tagging_to_conversations -->
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNeedsTaggingToConversations extends Migration
{
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('needs_tagging')->default(true);
        });
    }

    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('needs_tagging');
        });
    }
}
