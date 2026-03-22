@props([
    'label' => 'Row actions',
    'width' => 'w-40',
    'menuHeight' => 160,
])

<div
    {{ $attributes->class('flex items-center justify-end') }}
    x-data="{
        open: false,
        menuStyle: '',
        toggleMenu(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const menuHeight = {{ (int) $menuHeight }};
            const right = window.innerWidth - rect.right;

            if (window.innerHeight - rect.bottom < menuHeight) {
                this.menuStyle = 'bottom: ' + (window.innerHeight - rect.top + 6) + 'px; right: ' + right + 'px;';
            } else {
                this.menuStyle = 'top: ' + (rect.bottom + 6) + 'px; right: ' + right + 'px;';
            }

            this.open = ! this.open;
        },
        closeMenu() {
            this.open = false;
        }
    }"
    @keydown.escape.window="closeMenu()"
>
    <button
        type="button"
        @click="toggleMenu($event)"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
        aria-label="{{ $label }}"
    >
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="4" cy="10" r="1.5" />
            <circle cx="10" cy="10" r="1.5" />
            <circle cx="16" cy="10" r="1.5" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        @click.away="closeMenu()"
        class="fixed z-40 {{ $width }} overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
        :style="menuStyle"
    >
        {{ $slot }}
    </div>
</div>
