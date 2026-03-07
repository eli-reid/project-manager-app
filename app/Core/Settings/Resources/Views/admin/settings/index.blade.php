@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">
                <i class="fas fa-cogs me-2"></i> Settings Management
            </h1>
            <p class="text-muted">Manage application settings and configuration</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Export settings as JSON">
                <i class="fas fa-download me-1"></i> Export
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0">Setting Groups</h6>
                </div>
                <div class="card-body p-0">
                    <livewire:app.core.settings.livewire.settings-group-list />
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0">Edit Settings</h6>
                </div>
                <div class="card-body">
                    <livewire:app.core.settings.livewire.settings-editor />
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-group-item {
        transition: all 0.2s ease;
    }

    .settings-group-item:hover {
        background-color: #f8f9fa;
    }

    .settings-group-item.active {
        background-color: #e7f3ff;
        border-left: 3px solid #0d6efd;
    }

    .form-control-plaintext {
        padding-top: 0.375rem;
        padding-bottom: 0.375rem;
    }
</style>
@endsection
