<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\PettyCash;
use App\Models\ProductComparison;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Total saldo petty cash per bulan (saldo akhir di bulan ini)
        $pettyCashMonthlyBalance = (float) PettyCash::query()
            ->whereDate('date', '<=', $endOfMonth)
            ->whereDate('date', '>=', $startOfMonth)
            ->orderByDesc('number')
            ->value('balance') ?? 0;

        // Total jumlah product comparison
        $totalProductComparisons = ProductComparison::count();

        // Total jumlah purchase plan dan nilai total rupiahnya
        $totalPurchasePlans = PurchasePlan::count();
        $totalPurchasePlanValue = (float) PurchasePlanItem::sum('total_cost');

        // Total jumlah purchase request dan total nilai keseluruhan
        $totalPurchaseRequests = PurchaseRequest::count();
        $totalPurchaseRequestValue = (float) PurchaseRequestItem::sum('total_cost');

        // Total ticket
        $totalTickets = Ticket::count();

        // Total user
        $totalUsers = User::count();

        // Total employee
        $totalEmployees = Employee::count();

        return [
            Stat::make('Current Petty Cash Balance', 'Rp '.number_format($pettyCashMonthlyBalance, 0, ',', '.'))
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Product Comparison', number_format($totalProductComparisons, 0, ',', '.'))
                ->description('Jumlah seluruh product comparison')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),

            Stat::make('Total Purchase Planning', number_format($totalPurchasePlans, 0, ',', '.'))
                ->description('Nilai: Rp '.number_format($totalPurchasePlanValue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('warning'),

            Stat::make('Total Purchase Request', number_format($totalPurchaseRequests, 0, ',', '.'))
                ->description('Nilai: Rp '.number_format($totalPurchaseRequestValue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Total Ticket', number_format($totalTickets, 0, ',', '.'))
                ->description('Jumlah seluruh tiket')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('danger'),

            Stat::make('Total User', number_format($totalUsers, 0, ',', '.'))
                ->description('Pengguna terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Total Employee', number_format($totalEmployees, 0, ',', '.'))
                ->description('Jumlah karyawan')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),
        ];
    }
}
