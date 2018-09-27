<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSettingsTable extends Migration
{
    protected $conn;
    protected $table;
    protected $key;
    protected $value;
	protected $scope;
    protected $morphTable;
    protected $morphEntity;
    protected $morphKey;
    protected $morphValue;

    /**
     * CreateSettingsTable constructor.
     */
    public function __construct()
    {
        $this->conn  = config('settings.stores.database.connection');

        $this->table = config('settings.stores.database.names.settings.table');
        $this->key   = config('settings.stores.database.names.settings.key');
        $this->value = config('settings.stores.database.names.settings.value');
        $this->scope = config('settings.stores.database.names.settings.scope');

        $this->morphTable  = config('settings.stores.database.names.settings_models.table');
        $this->morphEntity = config('settings.stores.database.names.settings_models.entity');
        $this->morphKey    = config('settings.stores.database.names.settings_models.key');
        $this->morphValue  = config('settings.stores.database.names.settings_models.value');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->conn)->create($this->table, function(Blueprint $table)
        {
            $table->string($this->scope);
            $table->string($this->key);
            $table->text($this->value)->nullable();
            $table->primary([$this->scope, $this->key]);
        });

        Schema::connection($this->conn)->create($this->morphTable, function(Blueprint $table)
        {
            $table->morphs($this->morphEntity);
			$table->string($this->morphKey);
			$table->text($this->morphValue)->nullable();
            $table->primary([$this->morphEntity.'_id', $this->morphEntity.'_type', $this->morphKey]);
        });

		app('settings')->clearCache();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->conn)->dropIfExists($this->table);
        Schema::connection($this->conn)->dropIfExists($this->morphTable);
    }
}