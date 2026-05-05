<?php

namespace App\Http\Controllers\Doctor;

use App\Events\AppointmentIncomingCallAnnounced;
use App\Models\Appointment;
use App\Support\DoctorAgoraChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorAppointmentRealtimeController
{
    public function notifyCall(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'agora_app_id' => ['required', 'string'],
            'agora_token' => ['required', 'string'],
            'agora_channel' => ['required', 'string'],
            'call_type' => ['required', 'in:video,audio'],
        ]);

        broadcast(new AppointmentIncomingCallAnnounced(
            $appointment->getKey(),
            $validated['agora_app_id'],
            $validated['agora_token'],
            $validated['agora_channel'],
            $validated['call_type'],
        ));

        return response()->json(['ok' => true]);
    }

    public function refreshAgoraToken(Request $request, Appointment $appointment): JsonResponse
    {
        if (config('agora.AGORA_APP_ID') === '' || config('agora.AGORA_APP_CERTIFICATE') === '') {
            return response()->json(['message' => 'Agora is not configured.'], 503);
        }

        $channelName = DoctorAgoraChannel::channelName($appointment);
        $token = DoctorAgoraChannel::buildRtcToken($channelName);

        return response()->json([
            'agora_app_id' => config('agora.AGORA_APP_ID'),
            'agora_token' => $token,
            'agora_channel' => $channelName,
        ]);
    }
}
