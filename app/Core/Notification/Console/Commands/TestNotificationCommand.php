<?php

namespace App\Core\Notification\Console\Commands;

use App\Core\Notification\DTO\NotificationMessage;
use App\Core\Notification\Services\NotificationDispatcher;
use App\Core\Notification\Services\NotificationChannelRegistry;
use Illuminate\Console\Command;

final class TestNotificationCommand extends Command
{
    protected $signature = 'notification:test {type=generic} {--channels=} {--recipients=} {--title=} {--body=} {--data=} {--notifiable=} {--metadata=}';

    protected $description = 'Send a test notification through one or more channels';

    private NotificationDispatcher $dispatcher;
    private NotificationChannelRegistry $channelRegistry;

    public function __construct(NotificationDispatcher $dispatcher, NotificationChannelRegistry $channelRegistry)
    {
        parent::__construct();

        $this->dispatcher = $dispatcher;
        $this->channelRegistry = $channelRegistry;
    }

    public function handle(): int
    {
        $type = (string) $this->argument('type');

        $channelsOpt = (string) ($this->option('channels') ?? '');
        $channels = $channelsOpt === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $channelsOpt))));

        // If no channels specified, use all registered channels from the registry
        if (empty($channels)) {
            $channels = $this->channelRegistry->all();
        }

        $recipientsOpt = (string) ($this->option('recipients') ?? '');
        $recipients = $recipientsOpt === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $recipientsOpt))));

        $title = $this->option('title') ?: null;
        $body = $this->option('body') ?: null;

        $data = [];
        $dataOpt = $this->option('data');
        if (is_string($dataOpt) && $dataOpt !== '') {
            $decoded = json_decode($dataOpt, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON provided to --data');

                return 1;
            }

            $data = $decoded;
        }

        $metadata = [];

        // parse --notifiable in the form "user:1" or a fully-qualified class like "App\\Core\\Identity\\Models\\User:1"
        $notifiableOpt = (string) ($this->option('notifiable') ?? '');
        if ($notifiableOpt !== '') {
            if (str_contains($notifiableOpt, ':')) {
                [$typeStr, $idStr] = explode(':', $notifiableOpt, 2);

                $typeStr = trim($typeStr);
                $idStr = trim($idStr);

                if ($typeStr !== '' && $idStr !== '') {
                    if (str_starts_with($typeStr, 'App\\')) {
                        $metadata['notifiable_type'] = $typeStr;
                    } else {
                        // map short names like "user" to known models
                        if (strtolower($typeStr) === 'user') {
                            $metadata['notifiable_type'] = \App\Core\Identity\Models\User::class;
                        } else {
                            $metadata['notifiable_type'] = $typeStr;
                        }
                    }

                    $metadata['notifiable_id'] = $idStr;
                }
            }
        }

        // parse additional metadata JSON
        $metaOpt = $this->option('metadata');
        if (is_string($metaOpt) && $metaOpt !== '') {
            $decodedMeta = json_decode($metaOpt, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON provided to --metadata');

                return 1;
            }

            $metadata = array_merge($metadata, $decodedMeta);
        }

        $message = new NotificationMessage($type, $title, $body, $data, $recipients);

        // attach metadata if present
        if (! empty($metadata)) {
            $message = $message->withMergedMetadata($metadata);
        }

        $results = $this->dispatcher->dispatch($message, $channels);

        $out = [];
        foreach ($results as $name => $res) {
            $out[$name] = $res instanceof \App\Core\Notification\DTO\ChannelResult ? $res->toArray() : (is_array($res) ? $res : ['result' => (string) $res]);
        }

        $this->line(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
