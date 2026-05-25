<?php

namespace App\Domains\Projects\Notifications;

use App\Core\Identity\Models\User;
use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Services\NotificationPreferenceService;
use App\Domains\Projects\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ProjectAccessRevokedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Project $project,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        return app(NotificationPreferenceService::class)->resolveChannels(
            $notifiable,
            $this->notificationKey(),
            ['mail', 'database', SmsChannel::class, WebPushChannel::class],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Project Access Revoked: '.$this->project->name)
            ->markdown('projects::emails.notifications.project-access-revoked', [
                'project' => $this->project,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->notificationKey(),
            'project_id' => (string) $this->project->id,
            'project_name' => $this->project->name,
        ];
    }

    /**
     * @return array{to:string|null,message:string}|null
     */
    public function toSms(object $notifiable): ?array
    {
        $phone = $notifiable->phone ?? null;

        if (! is_string($phone) || $phone === '') {
            return null;
        }

        return [
            'to' => $phone,
            'message' => 'Your access to project '.$this->project->name.' has been revoked.',
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Project access revoked')
            ->body('Your access to '.$this->project->name.' was removed.')
            ->icon('/icon-192.png')
            ->badge('/icon-192.png')
            ->tag('project-access-revoked-'.(string) $this->project->id)
            ->data([
                'url' => route('projects.index'),
                'key' => $this->notificationKey(),
                'project_id' => (string) $this->project->id,
                'project_name' => $this->project->name,
            ]);
    }

    private function notificationKey(): string
    {
        return ProjectNotificationDefinitions::ACCESS_REVOKED;
    }
}
