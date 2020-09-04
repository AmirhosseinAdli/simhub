<?php


namespace App\Models;

use Kernel\Model;

class Product extends Model
{
    protected $fillable = ['title', 'count'];
}