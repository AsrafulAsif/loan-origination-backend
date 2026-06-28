<?php

namespace App\Models\Product;

use App\Models\BaseModel;
class Product extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'products';

    protected $fillable = [
        'product_name',
        'product_code',
        'product_type',
        'workflow_definition_id',
    ];
}
