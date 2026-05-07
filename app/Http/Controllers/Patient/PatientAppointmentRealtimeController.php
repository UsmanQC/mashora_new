<?php

namespace App\Http\Controllers\Patient;

use App\Events\AppointmentIncomingCallAnnounced;
use App\Events\AppointmentSessionStarted;
use App\Models\Appointment;
use App\Support\DoctorAgoraChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientAppointmentRealtimeController
{
    public function notifyCall(Request $request, Appointment $appointment): JsonResponse
    {
        abort_unless((int) $appointment->user_id === (int) auth()->id(), 403);

        $validated = $request->validate([
            'agora_app_id' => ['required', 'string'],
            'agora_token' => ['required', 'string'],
            'agora_channel' => ['required', 'string'],
            'call_type' => ['required', 'in:video,audio'],
        ]);

        $appointment->update([
            'status' => 'in_process',
            'actual_start_at' => now(),
            'extend_at' => now()->addMinutes(max(1, (int) $appointment->duration)),
        ]);
        $appointment->refresh();

        broadcast(new AppointmentIncomingCallAnnounced(
            $appointment->getKey(),
            $validated['agora_app_id'],
            $validated['agora_token'],
            $validated['agora_channel'],
            $validated['call_type'],
        ));

        broadcast(new AppointmentSessionStarted(
            $appointment->id,
            (string) $appointment->status,
            $appointment->actual_start_at?->toIso8601String(),
            $appointment->extend_at?->toIso8601String(),
        ));

        return response()->json([
            'ok' => true,
            'status' => (string) $appointment->status,
            'actual_start_at' => $appointment->actual_start_at?->toIso8601String(),
            'extend_at' => $appointment->extend_at?->toIso8601String(),
        ]);
    }

    public function refreshAgoraToken(Request $request, Appointment $appointment): JsonResponse
    {
        abort_unless((int) $appointment->user_id === (int) auth()->id(), 403);

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
