<?php


namespace App\database\migrations;


use Kernel\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        $this->create_table('Products', [
            $this->autoincremental('id', 7),
            $this->string('title', 150),
            $this->integer('count', 10),
        ]);
    }

    public function down()
    {
        $this->drop_table('Products');
    }
}