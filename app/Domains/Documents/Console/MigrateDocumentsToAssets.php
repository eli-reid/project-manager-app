<?php

namespace App\Domains\Documents\Console;

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\DTOs\AssetMeta;
use App\Core\Assets\Models\Asset;
use App\Domains\Documents\Models\Document;
use App\Core\Identity\Models\User;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateDocumentsToAssets extends Command
{
    protected $signature = 'documents:migrate-assets {--batch=100} {--ingest : Physically ingest/move files into Assets storage} {--delete-old : Delete original document files after successful ingest}';

    protected $description = 'Migrate existing documents rows into assets (creates Asset rows and links documents.asset_id)';

    public function handle(): int
    {
        $batch = (int) $this->option('batch');

        $this->info('Starting documents -> assets migration');

        $ingest = (bool) $this->option('ingest');
        $deleteOld = (bool) $this->option('delete-old');

        $query = Document::query()->whereNull('asset_id');

        $orchestrator = $ingest ? app(AssetOrchestratorContract::class) : null;


        $total = $query->count();
        $this->info("Found {$total} documents to migrate");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk($batch, function ($documents) use ($bar, $ingest, $orchestrator, $deleteOld): void {
            foreach ($documents as $document) {
                DB::transaction(function () use ($document, $ingest, $orchestrator, $deleteOld, $bar): void {
                    if (! $ingest) {
                        $asset = Asset::create([
                            'title' => null,
                            'original_name' => $document->original_name,
                            'mime_type' => $document->mime_type,
                            'size_bytes' => $document->file_size,
                            'storage_disk' => $document->storage_disk,
                            'storage_path' => $document->storage_path,
                            'folder_path' => $document->folder_path ?? null,
                            'created_by_id' => $document->uploaded_by_id ?? null,
                        ]);

                        $document->asset_id = $asset->id;
                        $document->save();

                        $bar->advance();

                        return;
                    }

                    // ingest mode: physically read the existing file and re-upload via orchestrator
                    try {
                        if (! Storage::disk($document->storage_disk)->exists($document->storage_path)) {
                            // fallback: create asset row with existing paths
                            $asset = Asset::create([
                                'title' => null,
                                'original_name' => $document->original_name,
                                'mime_type' => $document->mime_type,
                                'size_bytes' => $document->file_size,
                                'storage_disk' => $document->storage_disk,
                                'storage_path' => $document->storage_path,
                                'folder_path' => $document->folder_path ?? null,
                                'created_by_id' => $document->uploaded_by_id ?? null,
                            ]);

                            $document->asset_id = $asset->id;
                            $document->save();

                            $bar->advance();

                            return;
                        }

                        // Stream or download file to temporary local file
                        $tmpPath = tempnam(sys_get_temp_dir(), 'doc_mig_');
                        $stream = Storage::disk($document->storage_disk)->readStream($document->storage_path);
                        if ($stream === false) {
                            throw new \RuntimeException('Unable to read stream for '.$document->storage_path);
                        }

                        $tmpHandle = fopen($tmpPath, 'w');
                        if ($tmpHandle === false) {
                            throw new \RuntimeException('Unable to open temp file');
                        }

                        while (! feof($stream)) {
                            fwrite($tmpHandle, fread($stream, 1024 * 8));
                        }

                        fclose($tmpHandle);
                        if (is_resource($stream)) {
                            fclose($stream);
                        }

                        $uploadedFile = new UploadedFile($tmpPath, $document->original_name, $document->mime_type, null, true);

                        $uploader = null;
                        if ($document->uploaded_by_id) {
                            $uploader = User::find($document->uploaded_by_id);
                        }
                        $uploader = $uploader ?? User::first();

                        $meta = AssetMeta::fromArray([
                            'folderPath' => $document->folder_path ?? null,
                            'disk' => $document->storage_disk ?? null,
                        ]);

                        $asset = $orchestrator->uploadAsset($uploader, $uploadedFile, $meta);

                        // link and optionally delete original
                        $document->asset_id = $asset->id;
                        $document->save();

                        if ($deleteOld) {
                            Storage::disk($document->storage_disk)->delete($document->storage_path);
                        }

                        // cleanup temp file
                        @unlink($tmpPath);

                        $bar->advance();
                    } catch (\Throwable $e) {
                        // log and continue
                        $this->error('Failed to ingest document id '.$document->id.' : '.$e->getMessage());
                        // ensure temp file cleanup
                        if (isset($tmpPath) && file_exists($tmpPath)) {
                            @unlink($tmpPath);
                        }
                        $bar->advance();
                    }
                });
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Migration complete. Documents now reference assets via asset_id.');

        return 0;
    }
}
