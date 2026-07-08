<?php

namespace App\Http\Controllers\Patient;

use App\Events\AppointmentCallEnded;
use App\Models\Appointment;
use App\Services\AppointmentSessionService;
use App\Support\DoctorAgoraChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PatientAppointmentRealtimeController
{
    public static function pendingCallCacheKey(int $userId, int $appointmentId): string
    {
        return "patient_pending_call:{$userId}:{$appointmentId}";
    }

    public static function clearPendingIncomingCall(int $userId, int $appointmentId): void
    {
        Cache::forget(self::pendingCallCacheKey($userId, $appointmentId));
    }

    /**
     * @param  array{call_type: string, agora_app_id: string, agora_token: string, agora_channel: string}  $payload
     */
    public static function storePendingIncomingCall(int $userId, int $appointmentId, array $payload): void
    {
        Cache::put(
            self::pendingCallCacheKey($userId, $appointmentId),
            $payload,
            now()->addMinutes(15),
        );
    }

    public function notifyCall(Appointment $appointment, AppointmentSessionService $sessions): JsonResponse
    {
        abort_unless((int) $appointment->user_id === (int) auth()->id(), 403);

        return response()->json([
            'message' => __('patient.appointments.session_doctor_must_start'),
        ], 403);
    }

    public function pendingIncomingCall(Appointment $appointment): JsonResponse
    {
        abort_unless((int) $appointment->user_id === (int) auth()->id(), 403);

        if ((string) $appointment->status !== 'in_process') {
            self::clearPendingIncomingCall((int) auth()->id(), (int) $appointment->id);

            return response()->json(['pending' => false]);
        }

        $cached = Cache::get(self::pendingCallCacheKey((int) auth()->id(), (int) $appointment->id));

        if (! is_array($cached)) {
            return response()->json(['pending' => false]);
        }

        return response()->json([
            'pending' => true,
            'appointment_id' => (int) $appointment->id,
            'call_type' => (string) ($cached['call_type'] ?? 'video'),
            'agora_app_id' => (string) ($cached['agora_app_id'] ?? ''),
            'agora_token' => (string) ($cached['agora_token'] ?? ''),
            'agora_channel' => (string) ($cached['agora_channel'] ?? ''),
        ]);
    }

    public function endCall(Appointment $appointment): JsonResponse
    {
        abort_unless((int) $appointment->user_id === (int) auth()->id(), 403);

        $appointment->refresh();

        self::clearPendingIncomingCall((int) auth()->id(), (int) $appointment->id);

        broadcast(new AppointmentCallEnded(
            (int) $appointment->id,
            (int) $appointment->user_id,
            (string) $appointment->status,
        ));

        return response()->json(['ok' => true]);
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
