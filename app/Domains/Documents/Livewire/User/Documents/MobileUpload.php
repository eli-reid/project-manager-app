<?php

namespace App\Domains\Documents\Livewire\User\Documents;

use App\Domains\Documents\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Livewire\Component;
use Livewire\WithFileUploads;

class MobileUpload extends Component
{
    use WithFileUploads;

    public $file;

    public $description = '';

    public $success = false;

    protected function rules(): array
    {
        return [
            'file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'txt'])->max(10240)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function upload()
    {
        $this->validate();

        $user = Auth::user();
        $path = $this->file->store('documents/user/'.$user->id, 'public');

        Document::create([
            'owner_scope' => Document::OWNER_SCOPE_USER,
            'owner_id' => $user->id,
            'original_name' => $this->file->getClientOriginalName(),
            'storage_disk' => 'public',
            'storage_path' => $path,
            'description' => $this->description,
            'uploaded_by_id' => $user->id,
            'visibility' => Document::VISIBILITY_PRIVATE,
        ]);

        $this->reset(['file', 'description']);
        $this->success = true;
        session()->flash('success', __('Document uploaded successfully.'));
    }

    public function render()
    {
        return view('documents::livewire.user.documents.mobile-upload');
    }
}
