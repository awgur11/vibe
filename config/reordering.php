<?php

use App\Models\Adv;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Filter;
use App\Models\Fvalue;
use App\Models\Page;
use App\Models\Picture;
use App\Models\Slide;

return [
    'resources' => [
        'brands' => [
            'model' => Brand::class,
            'column' => 'position',
        ],
        'advs' => [
            'model' => Adv::class,
            'column' => 'position',
        ],
        'pictures' => [
            'model' => Picture::class,
            'column' => 'position',
            'scope' => ['product_id' => ['required', 'integer', 'exists:products,id']],
        ],
        'fvalues' => [
            'model' => Fvalue::class,
            'column' => 'position',
            'scope' => ['filter_id' => ['required', 'integer', 'exists:filters,id']],
        ],
        'filters' => [
            'model' => Filter::class,
            'column' => 'position',
        ],
        'catalogs' => [
            'model' => Catalog::class,
            'column' => 'position',
            'scope' => ['parent_id' => ['nullable', 'integer', 'exists:catalogs,id']],
        ],
        'slides' => [
            'model' => Slide::class,
            'column' => 'position',
        ],
        'pages' => [
            'model' => Page::class,
            'column' => 'position',
            'where' => [['type', '!=', 'articles']],
        ],
    ],
];
