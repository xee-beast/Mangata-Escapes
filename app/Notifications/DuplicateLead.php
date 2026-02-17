<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DuplicateLead extends Notification implements ShouldQueue
{
    use Queueable;

    public $lead;
    public $submissionData;

    public function __construct(Lead $lead, array $submissionData)
    {
        $this->lead = $lead;
        $this->submissionData = $submissionData;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $submissionCouple = '';
        if (!empty($this->submissionData['bride_first_name']) || !empty($this->submissionData['groom_first_name'])) {
            $bride = trim(($this->submissionData['bride_first_name'] ?? '') . ' ' . ($this->submissionData['bride_last_name'] ?? ''));
            $groom = trim(($this->submissionData['groom_first_name'] ?? '') . ' ' . ($this->submissionData['groom_last_name'] ?? ''));
            $submissionCouple = trim($bride . ' - ' . $groom);
        }

        $message = (new MailMessage)
            ->subject('Duplicate Lead Submission - Contact Us Form')
            ->greeting('Duplicate Lead Submission')
            ->line('A contact form submission was received with an email address that already exists in the system.')
            ->line('**Existing Lead Information:**')
            ->line('Wedding Couple: ' . $this->lead->name)
            ->line('Email: ' . $this->lead->email)
            ->line('Phone: ' . ($this->lead->phone ?? 'N/A'))
            ->line('Status: ' . $this->lead->status)
            ->line('**New Submission Data:**');

        if ($submissionCouple) {
            $message->line('Wedding Couple: ' . $submissionCouple);
        }
        if (!empty($this->submissionData['phone'])) {
            $message->line('Phone: ' . $this->submissionData['phone']);
        }
        if (!empty($this->submissionData['email'])) {
            $message->line('Email: ' . $this->submissionData['email']);
        }
        if (isset($this->submissionData['text_agreement']) && $this->submissionData['text_agreement']) {
            $message->line('Text Agreement: Yes');
        }
        if (!empty($this->submissionData['travel_agent_requested'])) {
            $message->line('Travel Agent Requested: ' . $this->submissionData['travel_agent_requested']);
        }
        if (!empty($this->submissionData['contacted_us_by'])) {
            $message->line('Contacted Us By: ' . $this->submissionData['contacted_us_by']);
        }
        if (!empty($this->submissionData['contacted_us_date'])) {
            $message->line('Contacted Us Date: ' . $this->submissionData['contacted_us_date']);
        }
        if (!empty($this->submissionData['departure'])) {
            $message->line('Group departing from: ' . $this->submissionData['departure']);
        }
        if (!empty($this->submissionData['destinations'])) {
            $message->line('Destination(s): ' . $this->submissionData['destinations']);
        }
        if (!empty($this->submissionData['wedding_date'])) {
            $message->line('Wedding Date: ' . $this->submissionData['wedding_date']);
        }
        if (isset($this->submissionData['wedding_date_confirmed'])) {
            $message->line('Wedding Date Confirmed: ' . ($this->submissionData['wedding_date_confirmed'] ? 'Yes' : 'No'));
        }
        if (!empty($this->submissionData['referral_source'])) {
            $message->line('Referral Source: ' . $this->submissionData['referral_source']);
        }
        if (!empty($this->submissionData['facebook_group'])) {
            $message->line('Facebook Group: ' . $this->submissionData['facebook_group']);
        }
        if (!empty($this->submissionData['referred_by'])) {
            $message->line('Referred By: ' . $this->submissionData['referred_by']);
        }
        if(!empty($this->submissionData['notes'])) {
            $message->line('Notes: ' . $this->submissionData['notes']);
        }
        if (!empty($this->submissionData['message'])) {
            $message->line('Message: ' . $this->submissionData['message']);
        }

        $message->action('View Lead in Dashboard', url(config('app.dashboard_url') . '/leads/' . $this->lead->id));

        return $message;
    }
}
