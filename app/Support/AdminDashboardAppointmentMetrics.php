<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors Mashorapwa-prod {@see DashboardController::index}
 * so Filament dashboard totals and weekly charts match the legacy admin.
 */
final class AdminDashboardAppointmentMetrics
{
    private static ?array $cache = null;

    /**
     * @return array{
     *     totalrevenue_today: float,
     *     totalrevenue_week: float,
     *     totalrevenue_month: float,
     *     totalrevenue_year: float,
     *     totalrevenue_all: float,
     *     confirmed_appointments_week: int,
     *     daily_avg_last7: float,
     *     chart_labels: list<string>,
     *     chart_confirmed_counts: list<int>,
     *     chart_daily_total_sales: list<float>,
     *     chart_daily_avg_sale: list<float>,
     *     appointments_today: int,
     *     appointments_yesterday: int,
     *     appointments_previous_month: int,
     *     appointments_current_year_ytd: int,
     *     appointments_last_year: int,
     *     doctors_total: int,
     *     users_today: int,
     *     users_week: int,
     *     users_month: int,
     * }
     */
    public static function data(): array
    {
        return self::$cache ??= self::compute();
    }

    private static function compute(): array
    {
        $allAppointments = Appointment::query()
            ->whereIn('status', ['new', 'in_process', 'completed'])
            ->whereDate('created_at', '>=', Carbon::now()->startOfYear())
            ->get();

        $todayDate = Carbon::now()->format('Y-m-d');
        $last7DaysDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $last30DaysDate = Carbon::now()->subDays(30)->format('Y-m-d');

        $todayAppointments = $allAppointments->filter(
            fn (Appointment $appointment): bool => Carbon::parse($appointment->created_at)->format('Y-m-d') === $todayDate,
        );

        $last7DaysAppointments = $allAppointments->filter(
            fn (Appointment $appointment): bool => Carbon::parse($appointment->created_at)->format('Y-m-d') > $last7DaysDate,
        );

        $last30DaysAppointments = $allAppointments->filter(
            fn (Appointment $appointment): bool => Carbon::parse($appointment->created_at)->format('Y-m-d') > $last30DaysDate,
        );

        $totalrevenueYear = (float) Appointment::query()
            ->whereIn('status', ['new', 'in_process', 'completed'])
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total');

        $totalrevenueAll = (float) Appointment::query()
            ->whereIn('status', ['new', 'in_process', 'completed'])
            ->sum('total');

        $chartLabels = [];
        $chartConfirmed = [];
        $chartDailyTotals = [];
        $chartDailyAvg = [];

        $startDate = Carbon::parse($last7DaysDate)->addDay()->format('Y-m-d');
        $endDate = $todayDate;

        while ($startDate <= $endDate) {
            $date = Carbon::parse($startDate)->format('Y-m-d');

            $chartLabels[] = Carbon::parse($startDate)->format('D');
            $chartConfirmed[] = $last7DaysAppointments
                ->filter(fn (Appointment $appointment): bool => Carbon::parse($appointment->created_at)->format('Y-m-d') === $date)
                ->count();

            $chartDailyTotals[] = (float) $last7DaysAppointments
                ->filter(fn (Appointment $appointment): bool => Carbon::parse($appointment->created_at)->format('Y-m-d') === $date)
                ->sum('total');

            $chartDailyAvg[] = (float) ($last7DaysAppointments
                ->filter(fn (Appointment $appointment): bool => Carbon::parse($appointment->created_at)->format('Y-m-d') === $date)
                ->avg('total') ?? 0);

            $startDate = Carbon::parse($date)->addDay()->format('Y-m-d');
        }

        $appointmentsYesterday = $allAppointments->filter(
            fn (Appointment $appointment): bool => Carbon::parse($appointment->created_at)->format('Y-m-d') === Carbon::now()->subDay()->format('Y-m-d'),
        )->count();

        $appointmentsPreviousMonth = $allAppointments->filter(
            fn (Appointment $appointment): bool => Carbon::parse($appointment->created_at)->format('Y-m') === Carbon::now()->subMonth()->format('Y-m'),
        )->count();

        $appointmentsLastYear = (int) Appointment::query()
            ->whereIn('status', ['new', 'in_process', 'completed'])
            ->whereYear('created_at', (int) Carbon::now()->subYear()->format('Y'))
            ->count();

        $usersPool = User::query()
            ->where('status', 1)
            ->whereDate('created_at', '>', Carbon::now()->subDays(30))
            ->get();

        $usersToday = $usersPool->filter(
            fn (User $user): bool => Carbon::parse($user->created_at)->format('Y-m-d') === $todayDate,
        )->count();

        $usersWeek = $usersPool->filter(
            fn (User $user): bool => Carbon::parse($user->created_at)->format('Y-m-d') > $last7DaysDate,
        )->count();

        $usersMonth = $usersPool->filter(
            fn (User $user): bool => Carbon::parse($user->created_at)->format('Y-m-d') > $last30DaysDate,
        )->count();

        return [
            'totalrevenue_today' => (float) $todayAppointments->sum('total'),
            'totalrevenue_week' => (float) $last7DaysAppointments->sum('total'),
            'totalrevenue_month' => (float) $last30DaysAppointments->sum('total'),
            'totalrevenue_year' => $totalrevenueYear,
            'totalrevenue_all' => $totalrevenueAll,
            'confirmed_appointments_week' => $last7DaysAppointments->count(),
            'daily_avg_last7' => (float) ($last7DaysAppointments->avg('total') ?? 0),
            'chart_labels' => $chartLabels,
            'chart_confirmed_counts' => $chartConfirmed,
            'chart_daily_total_sales' => $chartDailyTotals,
            'chart_daily_avg_sale' => $chartDailyAvg,
            'appointments_today' => $todayAppointments->count(),
            'appointments_yesterday' => $appointmentsYesterday,
            'appointments_previous_month' => $appointmentsPreviousMonth,
            'appointments_current_year_ytd' => $allAppointments->count(),
            'appointments_last_year' => $appointmentsLastYear,
            'doctors_total' => (int) DB::table('doctors')->count(),
            'users_today' => $usersToday,
            'users_week' => $usersWeek,
            'users_month' => $usersMonth,
        ];
    }
}
