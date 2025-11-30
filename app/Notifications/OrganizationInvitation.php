<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation as OrganizationInvitationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitation extends Notification
{
    use Queueable;

    public function __construct(public OrganizationInvitationModel $invitation) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $invitation = $this->invitation;
        $organization = $invitation->organization;
        $acceptUrl = url()->signedRoute('organizations.invitations.accept', $invitation);

        return (new MailMessage)
            ->subject(__('You have been invited to join :organization', ['organization' => $organization->name]))
            ->greeting(__('Hello!'))
            ->line(__('You have been invited to join the organization ":organization".', ['organization' => $organization->name]))
            ->action(__('Accept Invitation'), $acceptUrl)
            ->line(__('If you do not wish to join, you may ignore this email.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
