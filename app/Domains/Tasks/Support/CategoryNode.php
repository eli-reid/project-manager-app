<?php
declare(strict_types=1);

namespace App\Domains\Tasks\Support;

class CategoryNode
{
    public function __construct(
        public string $id,
        public ?string $parentId,
        public string $name,
        public array $children = []
    ) {
    }

    public function addChild(CategoryNode $child): void
    {
        $this->children[] = $child;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'children' => array_map(fn(CategoryNode $c) => $c->toArray(), $this->children),
        ];
    }
}
