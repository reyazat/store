<?php

namespace IamApi\Mail;

use IamApi\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param OrganizationInvitation $invitation دعوتنامه سازمان
     * @param string|null            $inviteUrl  لینک پذیرش دعوتنامه (اختیاری)
     */
    public function __construct(
        public readonly OrganizationInvitation $invitation,
        public readonly ?string $inviteUrl = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $orgName = $this->invitation->organization->name ?? config('app.name', 'سازمان');

        return new Envelope(
            subject: 'دعوت‌نامه عضویت در ' . $orgName . ' — ' . config('app.name', 'سامانه'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $organization = $this->invitation->organization;

        // Build invite URL from config if not provided
        $inviteUrl = $this->inviteUrl
            ?? rtrim(config('app.frontend_url', config('app.url', '')), '/')
               . '/invitations/accept?token=' . $this->invitation->token;

        return new Content(
            view: 'iam::mail.organization-invitation',
            with: [
                'invitation'   => $this->invitation,
                'organization' => $organization,
                'inviteUrl'    => $inviteUrl,
                'appName'      => config('app.name', 'سامانه'),
                'roleLabel'    => $this->getRoleLabel($this->invitation->role),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get human-readable label for role
     */
    private function getRoleLabel(string $role): string
    {
        $labels = [
            'owner'  => 'مالک',
            'admin'  => 'مدیر',
            'member' => 'عضو',
            'viewer' => 'بازدیدکننده',
        ];

        return $labels[$role] ?? $role;
    }
}
