<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class FinancialController extends Controller
{
    public function dashboard()
    {
        $stats = $this->getFinancialStats();
        $revenueChart = $this->getRevenueChartData();
        $subscriptionChart = $this->getSubscriptionChartData();
        $expiringSoon = $this->getExpiringSoonSubscriptions();

        return Inertia::render('Financial/Dashboard', [
            'stats' => $stats,
            'revenueChart' => $revenueChart,
            'subscriptionChart' => $subscriptionChart,
            'expiringSoon' => $expiringSoon,
        ]);
    }

    public function subscriptions(Request $request)
    {
        $query = CompanySubscription::with(['company', 'subscriptionPlan'])
            ->latest();

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('company', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan')) {
            $query->where('subscription_plan_id', $request->plan);
        }

        if ($request->filled('expires_in')) {
            $days = (int) $request->expires_in;
            $query->expiringSoon($days);
        }

        $subscriptions = $query->paginate(15);

        $plans = SubscriptionPlan::active()->get(['id', 'name']);

        return Inertia::render('Financial/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'plans' => $plans,
            'filters' => $request->only(['search', 'status', 'plan', 'expires_in']),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    public function payments(Request $request)
    {
        $query = Payment::with(['companySubscription.company', 'companySubscription.subscriptionPlan'])
            ->latest();

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('payment_id', 'like', "%{$search}%")
                  ->orWhereHas('companySubscription.company', function($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->paginate(15);

        return Inertia::render('Financial/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status', 'payment_method', 'date_from', 'date_to']),
            'statusOptions' => $this->getPaymentStatusOptions(),
            'paymentMethodOptions' => $this->getPaymentMethodOptions(),
        ]);
    }

    public function renewSubscription(CompanySubscription $subscription)
    {
        if (!$subscription->isExpired() && !$subscription->isExpiringSoon(30)) {
            return back()->withErrors(['error' => 'A assinatura não está próxima do vencimento.']);
        }

        $subscription->renew();

        return back()->with('success', 'Assinatura renovada com sucesso!');
    }

    public function cancelSubscription(CompanySubscription $subscription)
    {
        $subscription->cancel();

        return back()->with('success', 'Assinatura cancelada com sucesso!');
    }

    public function suspendSubscription(CompanySubscription $subscription)
    {
        $subscription->suspend();

        return back()->with('success', 'Assinatura suspensa com sucesso!');
    }

    public function reactivateSubscription(CompanySubscription $subscription)
    {
        $subscription->reactivate();

        return back()->with('success', 'Assinatura reativada com sucesso!');
    }

    public function extendTrial(CompanySubscription $subscription, Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        if (!$subscription->isTrial()) {
            return back()->withErrors(['error' => 'Apenas assinaturas em trial podem ter o período estendido.']);
        }

        $newTrialEnd = $subscription->current_period_end->addDays($request->days);
        
        $subscription->update([
            'current_period_end' => $newTrialEnd,
        ]);

        return back()->with('success', "Trial estendido por {$request->days} dias!");
    }

    private function getFinancialStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $currentMonthRevenue = Payment::paid()
            ->where('paid_at', '>=', $currentMonth)
            ->sum('amount');

        $lastMonthRevenue = Payment::paid()
            ->where('paid_at', '>=', $lastMonth)
            ->where('paid_at', '<', $currentMonth)
            ->sum('amount');

        $totalRevenue = Payment::paid()->sum('amount');

        $activeSubscriptions = CompanySubscription::active()->count();
        $trialSubscriptions = CompanySubscription::trial()->count();
        $expiredSubscriptions = CompanySubscription::expired()->count();

        $pendingPayments = Payment::pending()->count();
        $overduePayments = Payment::overdue()->count();

        $revenueGrowth = $lastMonthRevenue > 0 
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
            : 0;

        return [
            'total_revenue' => $totalRevenue,
            'current_month_revenue' => $currentMonthRevenue,
            'revenue_growth' => $revenueGrowth,
            'active_subscriptions' => $activeSubscriptions,
            'trial_subscriptions' => $trialSubscriptions,
            'expired_subscriptions' => $expiredSubscriptions,
            'pending_payments' => $pendingPayments,
            'overdue_payments' => $overduePayments,
        ];
    }

    private function getRevenueChartData(): array
    {
        $months = collect();
        
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = Payment::paid()
                ->whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('amount');
                
            $months->push([
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
            ]);
        }

        return $months->toArray();
    }

    private function getSubscriptionChartData(): array
    {
        return [
            ['status' => 'Ativo', 'count' => CompanySubscription::active()->count()],
            ['status' => 'Trial', 'count' => CompanySubscription::trial()->count()],
            ['status' => 'Expirado', 'count' => CompanySubscription::expired()->count()],
            ['status' => 'Cancelado', 'count' => CompanySubscription::where('status', 'cancelled')->count()],
            ['status' => 'Suspenso', 'count' => CompanySubscription::where('status', 'suspended')->count()],
        ];
    }

    private function getExpiringSoonSubscriptions(): array
    {
        return CompanySubscription::with(['company', 'subscriptionPlan'])
            ->expiringSoon(30)
            ->orderBy('current_period_end')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getStatusOptions(): array
    {
        return [
            ['value' => 'trial', 'label' => 'Trial'],
            ['value' => 'active', 'label' => 'Ativo'],
            ['value' => 'suspended', 'label' => 'Suspenso'],
            ['value' => 'cancelled', 'label' => 'Cancelado'],
            ['value' => 'expired', 'label' => 'Expirado'],
        ];
    }

    private function getPaymentStatusOptions(): array
    {
        return [
            ['value' => 'pending', 'label' => 'Pendente'],
            ['value' => 'processing', 'label' => 'Processando'],
            ['value' => 'paid', 'label' => 'Pago'],
            ['value' => 'failed', 'label' => 'Falhou'],
            ['value' => 'cancelled', 'label' => 'Cancelado'],
            ['value' => 'refunded', 'label' => 'Estornado'],
        ];
    }

    private function getPaymentMethodOptions(): array
    {
        return [
            ['value' => 'credit_card', 'label' => 'Cartão de Crédito'],
            ['value' => 'debit_card', 'label' => 'Cartão de Débito'],
            ['value' => 'bank_transfer', 'label' => 'Transferência Bancária'],
            ['value' => 'pix', 'label' => 'PIX'],
            ['value' => 'boleto', 'label' => 'Boleto'],
        ];
    }
}
