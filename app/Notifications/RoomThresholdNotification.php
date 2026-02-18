<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\RoomBlock;
use Illuminate\Notifications\Messages\MailMessage;

class RoomThresholdNotification extends BaseNotification
{
    protected $group;
    protected $roomBlock;
    protected $roomBookedCount;
    protected $percentage;

    public function __construct(Group $group, RoomBlock $roomBlock, int $roomBookedCount, float $percentage)
    {
        $this->group = $group;
        $this->roomBlock = $roomBlock;
        $this->roomBookedCount = $roomBookedCount;
        $this->percentage= $percentage;
    }


    protected function channels()
    {
        return ['mail'];
    }


    public function toMail()
    {
        $dashLink = config('app.dashboard_url') . '/groups/' . $this->group->id;

        return (new MailMessage)
            ->subject("{$this->group->id_at_provider} - {$this->group->name} - Limited Inventory")
            ->greeting($this->roomBlock->room->name)
            ->line('The ' . $this->roomBlock->room->name . ' room is ' . number_format($this->percentage, 2) . '% booked under ' . $this->group->name . '\'s group.')
            ->line('Originally Blocked: ' . $this->roomBookedCount)
            ->line('Currently Remaining: ' . ($this->roomBlock->inventory - $this->roomBookedCount))
            ->action('View Group in Dashboard', $dashLink);
    }
}
