<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Requests\StoreDeviceTokenRequest;
use App\Services\DeviceTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController
{
    public function store(StoreDeviceTokenRequest $request, DeviceTokenService $deviceTokens): JsonResponse
    {
        $doctor = $request->user('doctor');

        abort_unless($doctor !== null, 401);

        $deviceTokens->register($doctor, $request->string('device_token')->toString());

        return response()->json([
            'message' => 'Device token registered.',
        ]);
    }

    public function destroy(Request $request, DeviceTokenService $deviceTokens): JsonResponse
    {
        $doctor = $request->user('doctor');

        abort_unless($doctor !== null, 401);

        $validated = $request->validate([
            'device_token' => ['required', 'string', 'min:10', 'max:512'],
        ]);

        $deviceTokens->unregister($doctor, $validated['device_token']);

        return response()->json([
            'message' => 'Device token removed.',
        ]);
    }
}
