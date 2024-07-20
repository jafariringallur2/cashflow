<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToItemsTable extends Migration
{
   /**
     * Add the 'type' column to the 'items' table.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name'); // Add the 'type' column
        });
    }

    /**
     * Remove the 'type' column from the 'items' table.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('type'); // Remove the 'type' column
        });
    }
}
