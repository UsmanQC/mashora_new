<?php

namespace App\Support;

use App\Models\Appointment;
use Yasser\Agora\RtcTokenBuilder;

class DoctorAgoraChannel
{
    public static function channelName(Appointment $appointment): string
    {
        return 'video_call_'.$appointment->getKey();
    }

    public static function buildRtcToken(string $channelName): string
    {
        $appId = (string) config('agora.AGORA_APP_ID');
        $certificate = (string) config('agora.AGORA_APP_CERTIFICATE');

        $role = RtcTokenBuilder::RolePublisher;
        $expireTimeInSeconds = 7200;
        $privilegeExpiredTs = time() + $expireTimeInSeconds;

        return RtcTokenBuilder::buildTokenWithUid(
            $appId,
            $certificate,
            $channelName,
            0,
            $role,
            $privilegeExpiredTs,
        );
    }
}
