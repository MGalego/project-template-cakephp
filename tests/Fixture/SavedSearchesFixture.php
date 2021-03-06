<?php

namespace App\Test\Fixture;

use Search\Test\Fixture\SavedSearchesFixture as BaseFixture;

class SavedSearchesFixture extends BaseFixture
{
    public $import = ['model' => 'Search.SavedSearches'];

    public $fields = [
        'system' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
    ];
}
