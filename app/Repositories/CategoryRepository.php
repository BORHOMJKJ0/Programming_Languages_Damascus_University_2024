<?php

namespace App\Repositories;

use App\Models\Category\Category;

class CategoryRepository
{
    public function getAll($items)
    {
        return Category::paginate($items);
    }

    public function orderBy($column, $direction, $items)
    {
        return Category::orderBy($column, $direction)->paginate($items);
    }
}
