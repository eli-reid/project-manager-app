<?php
declare(strict_types=1);

namespace App\Domains\Tasks\Support;

use Illuminate\Support\Collection;

class CategoryTree
{
    /** @var array<string, CategoryNode> */
    protected array $nodes = [];

    public static function fromFlatList(array|Collection $categories): self
    {
        $self = new self();
        $items = $categories instanceof Collection ? $categories->all() : $categories;

        foreach ($items as $item) {
            $id = (string)($item['id'] ?? $item->id ?? '');
            $parentId = isset($item['parent_id']) ? ($item['parent_id'] === null ? null : (string)$item['parent_id']) : (isset($item->parentId) ? (string)$item->parentId : null);
            $name = (string)($item['name'] ?? $item->name ?? '');

            $self->nodes[$id] = new CategoryNode($id, $parentId, $name, []);
        }

        foreach ($self->nodes as $node) {
            if ($node->parentId !== null && isset($self->nodes[$node->parentId])) {
                $self->nodes[$node->parentId]->addChild($node);
            }
        }

        return $self;
    }

    public function find(string $id): ?CategoryNode
    {
        return $this->nodes[$id] ?? null;
    }

    public function branchIds(string $rootId): array
    {
        $root = $this->find($rootId);
        if (!$root) {
            return [];
        }

        $result = [];
        $this->traverseNode($root, function (CategoryNode $n) use (&$result) {
            $result[] = $n->id;
        });

        return $result;
    }

    protected function traverseNode(CategoryNode $node, callable $cb): void
    {
        $cb($node);
        foreach ($node->children as $child) {
            $this->traverseNode($child, $cb);
        }
    }

    public function descendantIds(string $id): array
    {
        $ids = $this->branchIds($id);
        // remove the root itself to return only descendants
        array_shift($ids);
        return $ids;
    }

    public function siblingIds(string $id): array
    {
        $node = $this->find($id);
        if (!$node) {
            return [];
        }

        if ($node->parentId === null) {
            $result = [];
            foreach ($this->nodes as $n) {
                if ($n->parentId === null && $n->id !== $id) {
                    $result[] = $n->id;
                }
            }
            return $result;
        }

        $parent = $this->find($node->parentId);
        if (!$parent) {
            return [];
        }

        return array_map(fn(CategoryNode $c) => $c->id, array_filter($parent->children, fn(CategoryNode $c) => $c->id !== $id));
    }

    public function pathToRoot(string $id): array
    {
        $path = [];
        $node = $this->find($id);
        while ($node !== null) {
            $path[] = $node->id;
            $node = $node->parentId ? $this->find($node->parentId) : null;
        }
        return $path;
    }

    public function toNestedArray(): array
    {
        $roots = [];
        foreach ($this->nodes as $node) {
            if ($node->parentId === null) {
                $roots[] = $node;
            }
        }

        return array_map(fn(CategoryNode $n) => $n->toArray(), $roots);
    }
}
