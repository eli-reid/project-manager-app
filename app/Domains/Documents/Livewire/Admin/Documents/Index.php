<?php

namespace App\Domains\Documents\Livewire\Admin\Documents;

use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Services\DocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Number;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Documents Admin')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'scope')]
    public string $filterOwnerScope = '';

    #[Url(as: 'disk')]
    public string $filterDisk = '';

    public function mount(): void
    {
        $this->authorize('deleteAny', Document::class);
        $this->authorize('manageStorage', Document::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterOwnerScope(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDisk(): void
    {
        $this->resetPage();
    }

    public function deleteDocument(string $documentId, DocumentService $documentService): void
    {
        $this->authorize('deleteAny', Document::class);

        $document = Document::query()->findOrFail($documentId);
        $documentService->deleteDocument($document);

        session()->flash('success', 'Document deleted.');
    }

    public function render()
    {
        $this->authorize('manageStorage', Document::class);

        $documentsQuery = Document::query()
            ->with(['uploadedBy:id,first_name,last_name', 'ownerUser:id,first_name,last_name', 'ownerProject:id,name,project_number'])
            ->latest();

        if ($this->search !== '') {
            $documentsQuery->where(function ($query): void {
                $query->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('original_name', 'like', '%'.$this->search.'%')
                    ->orWhere('storage_path', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterOwnerScope !== '') {
            $documentsQuery->where('owner_scope', $this->filterOwnerScope);
        }

        if ($this->filterDisk !== '') {
            $documentsQuery->where('storage_disk', $this->filterDisk);
        }

        return view('documents::livewire.admin.documents.index', [
            'documents' => $documentsQuery->paginate(20),
            'summary' => $this->summary(),
            'disks' => $this->diskSummaries(),
            'ownerScopes' => [
                Document::OWNER_SCOPE_USER => 'User Owned',
                Document::OWNER_SCOPE_PROJECT => 'Project Owned',
            ],
        ]);
    }

    /**
     * @return array{documents_count:int,total_bytes:int,user_owned_count:int,project_owned_count:int,global_count:int}
     */
    private function summary(): array
    {
        return [
            'documents_count' => Document::query()->count(),
            'total_bytes' => (int) (Document::query()->sum('file_size') ?? 0),
            'user_owned_count' => Document::query()->userOwned()->count(),
            'project_owned_count' => Document::query()->projectOwned()->count(),
            'global_count' => Document::query()->global()->count(),
        ];
    }

    /**
     * @return array<int, array{disk:string,documents_count:int,total_bytes:int,free_bytes:int|null,total_space_bytes:int|null,root:string|null}>
     */
    private function diskSummaries(): array
    {
        return Document::query()
            ->selectRaw('storage_disk, COUNT(*) as documents_count, COALESCE(SUM(file_size), 0) as total_bytes')
            ->groupBy('storage_disk')
            ->orderBy('storage_disk')
            ->get()
            ->map(function (Document $document): array {
                $disk = (string) $document->storage_disk;
                $root = Config::get("filesystems.disks.{$disk}.root");
                $rootPath = is_string($root) && $root !== '' ? $root : null;
                $totalSpace = $rootPath !== null && @is_dir($rootPath) ? @disk_total_space($rootPath) : false;
                $freeSpace = $rootPath !== null && @is_dir($rootPath) ? @disk_free_space($rootPath) : false;

                return [
                    'disk' => $disk,
                    'documents_count' => (int) $document->documents_count,
                    'total_bytes' => (int) $document->total_bytes,
                    'free_bytes' => is_numeric($freeSpace) ? (int) $freeSpace : null,
                    'total_space_bytes' => is_numeric($totalSpace) ? (int) $totalSpace : null,
                    'root' => $rootPath,
                ];
            })
            ->values()
            ->all();
    }

    public function formatBytes(int $bytes): string
    {
        return Number::fileSize(max($bytes, 0));
    }
}
