<?php


namespace App\database\seeds;


use App\Models\Product;
use Kernel\Seed;

class Products extends Seed
{
    public function run(){
        Product::create([
            'username' => 'admin',
            'password' => hash('sha1','123456'),
        ]);
    }
}