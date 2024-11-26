<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository
{
    public function getAll($items, $page)
    {
        return Category::paginate($items, ['*'], 'page', $page);
    }

    public function orderBy($column, $direction, $page, $items)
    {
        return Category::orderBy($column, $direction)->paginate($items, ['*'], 'page', $page);
    }
}
