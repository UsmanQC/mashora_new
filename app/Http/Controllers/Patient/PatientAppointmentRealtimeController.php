<?php

namespace App\Http\Controllers\Patient;

use App\Models\Appointment;
use App\Services\AppointmentSessionService;
use App\Support\DoctorAgoraChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientAppointmentRealtimeController
{
    public function notifyCall(Appointment $appointment, AppointmentSessionService $sessions): JsonResponse
    {
        abort_unless((int) $appointment->user_id === (int) auth()->id(), 403);

        return response()->json([
            'message' => __('patient.appointments.session_doctor_must_start'),
        ], 403);
    }

    public function refreshAgoraToken(Request $request, Appointment $appointment, AppointmentSessionService $sessions): JsonResponse
    {
        abort_unless((int) $appointment->user_id === (int) auth()->id(), 403);

        if (! $sessions->canPatientJoin($appointment)) {
            return response()->json([
                'message' => __('patient.appointments.session_doctor_must_start'),
            ], 403);
        }

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
