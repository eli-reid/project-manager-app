<?php

declare(strict_types=1);

namespace App\Domains\Projects\Support;

final class ProjectTabViewItem
{
    public function __construct(
        public readonly ResolvedProjectTab $tab,
        public readonly string $key,
        public readonly string $label,
        public readonly ?string $modeQueryParam,
        public readonly ?string $detailQueryParam,
        public readonly int $sort,
        public readonly bool $isHidden = false,
    ) {}

    public static function fromResolvedTab(ResolvedProjectTab $tab, ?int $sort = null, bool $isHidden = false): self
    {
        return new self(
            tab: $tab,
            key: $tab->key(),
            label: $tab->label(),
            modeQueryParam: $tab->modeQueryParam(),
            detailQueryParam: $tab->detailQueryParam(),
            sort: $sort ?? $tab->sort(),
            isHidden: $isHidden,
        );
    }
}
