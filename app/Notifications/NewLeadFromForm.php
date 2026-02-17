<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadFromForm extends Notification implements ShouldQueue
{
    use Queueable;

    public $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {

        $message = (new MailMessage)
            ->subject('New Lead Created - Contact Us Form')
            ->greeting('New Lead Created')
            ->line('A new lead has been created from the contact us form.')
            ->line('Wedding Couple: ' . $this->lead->name);

        if ($this->lead->phone) {
            $message->line('Phone: ' . $this->lead->phone);
        }
        $message->line('Email: ' . $this->lead->email);
        if ($this->lead->text_agreement) {
            $message->line('Text Agreement: Yes');
        }
        if ($this->lead->travel_agent_requested) {
            $message->line('Travel Agent Requested: ' . $this->lead->travel_agent_requested);
        }
        if ($this->lead->contacted_us_by) {
            $message->line('Contacted Us By: ' . $this->lead->contacted_us_by);
        }
        if ($this->lead->contacted_us_date) {
            $message->line('Contacted Us Date: ' . $this->lead->contacted_us_date);
        }
        if ($this->lead->departure) {
            $message->line('Group departing from: ' . $this->lead->departure);
        }
        if ($this->lead->destinations) {
            $message->line('Destination(s): ' . $this->lead->destinations);
        }
        if ($this->lead->wedding_date) {
            $message->line('Wedding Date: ' . $this->lead->wedding_date);
        }
        if (isset($this->lead->wedding_date_confirmed)) {
            $message->line('Wedding Date Confirmed: ' . ($this->lead->wedding_date_confirmed ? 'Yes' : 'No'));
        }
        if ($this->lead->referral_source) {
            $message->line('Referral Source: ' . $this->lead->referral_source);
        }
        if ($this->lead->facebook_group) {
            $message->line('Facebook Group: ' . $this->lead->facebook_group);
        }
        if ($this->lead->referred_by) {
            $message->line('Referred By: ' . $this->lead->referred_by);
        }
        if ($this->lead->notes) {
            $message->line('Notes: ' . $this->lead->notes);
        }
        if ($this->lead->message) {
            $message->line('Message: ' . $this->lead->message);
        }

        $message->action('View Lead in Dashboard', url(config('app.dashboard_url') . '/leads/' . $this->lead->id));

        return $message;
    }
}
