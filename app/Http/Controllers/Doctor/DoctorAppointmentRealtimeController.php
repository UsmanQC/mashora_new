<?php

namespace App\Http\Controllers\Doctor;

use App\Events\AppointmentIncomingCallAnnounced;
use App\Events\PatientSessionJoinRequested;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentSessionService;
use App\Support\DoctorAgoraChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorAppointmentRealtimeController
{
    public function notifyCall(Request $request, Appointment $appointment, AppointmentSessionService $sessions): JsonResponse
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        $validated = $request->validate([
            'agora_app_id' => ['required', 'string'],
            'agora_token' => ['required', 'string'],
            'agora_channel' => ['required', 'string'],
            'call_type' => ['required', 'in:video,audio'],
        ]);

        $appointment = $sessions->ensureStartedForDoctor($doctor, $appointment);

        if ((string) $appointment->status !== 'in_process') {
            return response()->json(['message' => __('doctor.conversation.session_not_started')], 422);
        }

        broadcast(new AppointmentIncomingCallAnnounced(
            $appointment->getKey(),
            $validated['agora_app_id'],
            $validated['agora_token'],
            $validated['agora_channel'],
            $validated['call_type'],
        ));

        broadcast(new PatientSessionJoinRequested(
            (int) $appointment->user_id,
            (int) $appointment->id,
            (string) $validated['call_type'],
            (string) $validated['agora_app_id'],
            (string) $validated['agora_token'],
            (string) $validated['agora_channel'],
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
