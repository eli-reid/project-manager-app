<div class="list-group list-group-flush">
    @forelse($groups as $group)
        <button 
            type="button"
            class="list-group-item list-group-item-action text-start settings-group-item {{ $selectedGroup === $group->group ? 'active' : '' }}"
            wire:click="selectGroup('{{ $group->group }}')"
        >
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ ucfirst(str_replace('_', ' ', $group->group)) }}</strong>
                    <br>
                    <small class="text-muted">{{ $this->getGroupCount($group->group) }} settings</small>
                </div>
                <span class="badge bg-light text-dark">{{ $this->getGroupCount($group->group) }}</span>
            </div>
        </button>
    @empty
        <div class="p-3 text-center text-muted">
            <i class="fas fa-inbox"></i>
            <p class="mb-0">No setting groups found</p>
        </div>
    @endforelse
</div>
