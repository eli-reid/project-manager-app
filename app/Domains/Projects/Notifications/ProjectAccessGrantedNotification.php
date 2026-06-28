<?php

namespace App\Domains\Projects\Notifications;

use App\Core\Identity\Models\User;
use App\Core\Notification\Channels\RegistryBridgeChannel;
use App\Core\Notification\Contracts\RegistryNotification;
use App\Core\Notification\DTO\NotificationMessage;
use App\Domains\Projects\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class ProjectAccessGrantedNotification extends Notification implements RegistryNotification, ShouldQueue
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

        return [RegistryBridgeChannel::class];
    }

    public function toNotificationMessage(object $notifiable): NotificationMessage
    {
        return new NotificationMessage(
            type: $this->notificationKey(),
            title: 'Project Access Granted',
            body: 'You have been granted access to '.$this->project->name.'.',
            data: $this->toArray($notifiable),
            recipients: $this->resolveRecipients($notifiable),
            metadata: [
                'project_id' => (string) $this->project->id,
                'project_name' => $this->project->name,
            ],
        );
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
            'url' => route('projects.show', $this->project),
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
            'message' => 'You have been granted access to project: '.$this->project->name.'.',
        ];
    }

    public function notificationKey(): string
    {
        return ProjectNotificationDefinitions::ACCESS_GRANTED;
    }

    /**
     * @return array<int, string>
     */
    private function resolveRecipients(object $notifiable): array
    {
        $recipients = [];

        if (property_exists($notifiable, 'email') && is_string($notifiable->email) && $notifiable->email !== '') {
            $recipients[] = 'email:'.$notifiable->email;
        }

        if (property_exists($notifiable, 'phone') && is_string($notifiable->phone) && $notifiable->phone !== '') {
            $recipients[] = 'phone:'.$notifiable->phone;
        }

        return $recipients;
    }
}
