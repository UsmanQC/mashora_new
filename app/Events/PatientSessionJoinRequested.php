<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientSessionJoinRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $appointmentId,
        public string $callType,
        public string $agoraAppId = '',
        public string $agoraToken = '',
        public string $agoraChannel = '',
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('patient.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.join-requested';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'call_type' => $this->callType,
            'agora_app_id' => $this->agoraAppId,
            'agora_token' => $this->agoraToken,
            'agora_channel' => $this->agoraChannel,
        ];
    }
}
