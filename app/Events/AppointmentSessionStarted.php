<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentSessionStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $appointmentId,
        public string $status,
        public ?string $actualStartAt,
        public ?string $extendAt,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('appointment.'.$this->appointmentId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.started';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'status' => $this->status,
            'actual_start_at' => $this->actualStartAt,
            'extend_at' => $this->extendAt,
        ];
    }
}
