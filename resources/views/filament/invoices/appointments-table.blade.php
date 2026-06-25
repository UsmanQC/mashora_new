@php
    /** @var \App\Models\Invoice $record */
    $appointments = $record->appointments()
        ->orderBy('appointment_date')
        ->orderBy('start_time')
        ->get();
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    @if ($appointments->isEmpty())
        <p class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">No sessions on this invoice.</p>
    @else
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Reference</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Patient</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Phone</th>
                    <th class="px-4 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Total</th>
                    <th class="px-4 py-2 text-right font-semibold text-gray-600 dark:text-gray-300">Doctor share</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($appointments as $appointment)
                    <tr>
                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100">#{{ $appointment->appointment_number ?: $appointment->id }}</td>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                            {{ $appointment->appointment_date?->format('Y-m-d') ?? '—' }}
                            @if (filled($appointment->start_time))
                                <span class="text-xs text-gray-500">{{ \Illuminate\Support\Str::of($appointment->start_time)->substr(0, 5) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                            {{ $appointment->patient_name ?: '—' }}
                            @if (filled($appointment->patient_email))
                                <div class="text-xs text-gray-500">{{ $appointment->patient_email }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $appointment->patient_phone ?: '—' }}</td>
                        <td class="px-4 py-2 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ number_format((float) $appointment->total, 2) }} SAR</td>
                        <td class="px-4 py-2 text-right tabular-nums font-semibold text-emerald-600">{{ number_format((float) $appointment->doctor_share, 2) }} SAR</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
