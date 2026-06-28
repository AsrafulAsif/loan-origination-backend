<?php

namespace App\Models\Product;

use App\Models\BaseModel;

class ProductDetails extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 'product_details';

    protected $fillable = [
        'product_id',
        'role_name',
        'limit_amount',
        'customer_type',
    ];
}
