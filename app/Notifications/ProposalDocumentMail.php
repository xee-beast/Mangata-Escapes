<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;

class ProposalDocumentMail extends Notification
{
    use Queueable;

    protected $converted_object;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($converted_object)
    {
        $this->converted_object = $converted_object;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if ($this->converted_object->type === 'cv') {
            $type = 'Classic Vacations';
        } else if ($this->converted_object->type === 'ti') {
            $type = 'Travel Impressions';
        } else {
            $type = 'Envoyage';
        }

        return (new MailMessage)
            ->subject("Converted Proposal: {$this->converted_object->name} - {$type} - {$this->converted_object->resort}")
            ->greeting('HERE YOU GO!')
            ->line('Here is the proposal document that you requested to convert.')
            ->line($this->converted_object->name)
            ->line($this->converted_object->resort)
            ->line($this->converted_object->wedding_date)
            ->line($this->converted_object->travel_dates)
            ->attachData(
                FacadePdf::loadView('pdf.proposal', ['converted_object' => $this->converted_object])->output(),
                "Converted Proposal - {$this->converted_object->name} - {$type} - {$this->converted_object->resort}.pdf",
                ['mime' => 'application/pdf']
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
