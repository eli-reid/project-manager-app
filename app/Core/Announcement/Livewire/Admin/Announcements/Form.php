<?php

namespace App\Core\Announcement\Livewire\Admin\Announcements;

use App\Core\Announcement\Enums\AnnouncementType;
use App\Core\Announcement\Models\Announcement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Announcement Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Announcement $announcement = null;

    public bool $isEdit = false;

    public string $title = '';

    public string $content = '';

    public string $type = 'general';

    public bool $is_active = true;

    public bool $is_dismissable = false;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function mount(?Announcement $announcement = null): void
    {
        if ($announcement !== null && $announcement->exists) {
            $this->authorize('update', $announcement);

            $this->announcement = $announcement;
            $this->isEdit = true;
            $this->title = $announcement->title;
            $this->content = $announcement->content;
            $this->type = $announcement->type->value;
            $this->is_active = (bool) $announcement->is_active;
            $this->is_dismissable = (bool) $announcement->is_dismissable;
            $this->start_date = $announcement->start_date?->format('Y-m-d\\TH:i');
            $this->end_date = $announcement->end_date?->format('Y-m-d\\TH:i');

            return;
        }

        $this->authorize('create', Announcement::class);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::enum(AnnouncementType::class)],
            'is_active' => ['boolean'],
            'is_dismissable' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->isEdit) {
            $announcement = $this->announcement;
            if ($announcement === null) {
                return;
            }

            $this->authorize('update', $announcement);

            $announcement->update($validated);

            session()->flash('success', 'Announcement updated successfully.');
        } else {
            $this->authorize('create', Announcement::class);

            $announcement = new Announcement($validated);
            $announcement->created_by = (string) Auth::id();
            $announcement->save();

            session()->flash('success', 'Announcement created successfully.');
        }

        $this->redirectRoute('admin.announcements.index', navigate: true);
    }

    public function render()
    {
        return view('announcement::livewire.admin.announcements.form', [
            'types' => AnnouncementType::options(),
        ]);
    }
}
