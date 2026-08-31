<?php

namespace App\Domains\Documents\Livewire\User\Documents;

use App\Domains\Documents\Services\DocumentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Layout('layouts.mobile')]
#[Title('Upload Document')]
class MobileUpload extends Component
{
    use WithFileUploads;

    public $file;

    public $description = '';

    public $success = false;

    protected function rules(): array
    {
        $rules = app(DocumentService::class)->validationRules();

        return [
            'file' => ['required', 'file', 'max:'.$rules['max_kilobytes'], 'mimes:'.implode(',', $rules['allowed_extensions'])],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function submitUpload(DocumentService $documentService): void
    {
        $this->validate();

        $user = Auth::user();
        $documentService->uploadUserDocument($user, $this->file, [
            'description' => $this->description !== '' ? $this->description : null,
        ]);

        $this->reset(['file', 'description']);
        $this->success = true;
        session()->flash('success', __('Document uploaded successfully.'));
    }

    public function render()
    {
        return view('documents::livewire.mobile.documents.upload');
    }
}
