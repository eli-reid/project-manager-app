<?php

use App\Domains\Tasks\Support\CategoryTree;

it('builds a tree and returns branch and descendant ids', function () {
    $flat = [
        ['id' => '1', 'parent_id' => null, 'name' => 'Root'],
        ['id' => '2', 'parent_id' => '1', 'name' => 'Child A'],
        ['id' => '3', 'parent_id' => '1', 'name' => 'Child B'],
        ['id' => '4', 'parent_id' => '2', 'name' => 'Grandchild'],
    ];

    $tree = CategoryTree::fromFlatList($flat);

    expect($tree->branchIds('1'))->toEqual(['1', '2', '4', '3']);
    expect($tree->descendantIds('1'))->toEqual(['2', '4', '3']);
    expect($tree->descendantIds('2'))->toEqual(['4']);
    expect($tree->pathToRoot('4'))->toEqual(['4', '2', '1']);
});
