<?php

namespace App\Core\Notification\Console\Commands;

use App\Core\Notification\DTO\NotificationMessage;
use App\Core\Notification\Services\NotificationDispatcher;
use App\Core\Notification\Services\NotificationChannelRegistry;
use App\Core\Identity\Models\User;
use Illuminate\Console\Command;

final class TestNotificationCommand extends Command
{
    protected $signature = 'notification:test {type=generic} {--channels=} {--recipients=} {--title=} {--body=} {--data=} {--notifiable=} {--metadata=} {--list-channels}';

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

        // If --list-channels requested, print and exit
        if ($this->option('list-channels')) {
            $registered = $this->channelRegistry->all();
            if (empty($registered)) {
                $this->line('No channels registered.');
                return 0;
            }

            $this->line('Registered channels:');
            foreach ($registered as $ch) {
                $this->line(' - '.$ch);
            }

            return 0;
        }

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

        // Resolve recipients like "user:1" into email:... strings and PushSubscription objects
        $resolvedRecipients = [];
        foreach ($recipients as $rec) {
            if (! is_string($rec) || $rec === '') {
                continue;
            }

            if (str_starts_with($rec, 'user:')) {
                $userId = substr($rec, 5);
                $user = User::find($userId);
                if (! $user) {
                    $this->warn(sprintf('User not found for recipient %s', $rec));
                    continue;
                }

                // add email recipient if present
                if (! empty($user->email)) {
                    $resolvedRecipients[] = 'email:'.$user->email;
                } else {
                    $this->warn(sprintf('User %s has no email, skipping email recipient', $rec));
                }

                // add push subscription models if available
                if (method_exists($user, 'pushSubscriptions')) {
                    $subs = $user->pushSubscriptions()->get();
                    foreach ($subs as $sub) {
                        $resolvedRecipients[] = $sub;
                    }
                }

                // also ensure metadata notifiable is set if missing
                if (! isset($metadata['notifiable_type'])) {
                    $metadata['notifiable_type'] = User::class;
                    $metadata['notifiable_id'] = (string) $user->getKey();
                }

                continue;
            }

            // pass through other recipient formats (email:..., user:id etc.)
            $resolvedRecipients[] = $rec;
        }

        $message = new NotificationMessage($type, $title, $body, $data, $resolvedRecipients);

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
