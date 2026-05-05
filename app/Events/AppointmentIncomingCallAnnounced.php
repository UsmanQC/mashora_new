<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentIncomingCallAnnounced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $appointmentId,
        public string $agoraAppId,
        public string $agoraToken,
        public string $agoraChannel,
        public string $callType,
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
        return 'call.incoming';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'agora_app_id' => $this->agoraAppId,
            'agora_token' => $this->agoraToken,
            'agora_channel' => $this->agoraChannel,
            'call_type' => $this->callType,
        ];
    }
}
