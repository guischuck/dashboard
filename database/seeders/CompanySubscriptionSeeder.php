<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CompanySubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        $plans = SubscriptionPlan::all();

        if ($companies->isEmpty() || $plans->isEmpty()) {
            $this->command->info('Nenhuma empresa ou plano encontrado. Execute os seeders de Company e SubscriptionPlan primeiro.');
            return;
        }

        foreach ($companies as $company) {
            // Escolher um plano aleatório
            $plan = $plans->random();
            
            // Definir status da assinatura
            $statuses = ['trial', 'active', 'active', 'active', 'expired']; // Mais chances de ativo
            $status = $statuses[array_rand($statuses)];
            
            // Definir datas baseadas no status
            $now = now();
            
            if ($status === 'trial') {
                $currentPeriodStart = $now->copy()->subDays(rand(1, 20));
                $currentPeriodEnd = $currentPeriodStart->copy()->addDays($plan->trial_days);
                $trialEndsAt = $currentPeriodEnd;
            } else {
                $currentPeriodStart = $now->copy()->subDays(rand(1, 30));
                $currentPeriodEnd = match($plan->billing_cycle) {
                    'monthly' => $currentPeriodStart->copy()->addMonth(),
                    'quarterly' => $currentPeriodStart->copy()->addMonths(3),
                    'annual' => $currentPeriodStart->copy()->addYear(),
                    default => $currentPeriodStart->copy()->addMonth(),
                };
                $trialEndsAt = null;
                
                // Para assinaturas expiradas, ajustar datas
                if ($status === 'expired') {
                    $currentPeriodEnd = $now->copy()->subDays(rand(1, 30));
                }
            }

            $subscription = CompanySubscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'trial_ends_at' => $trialEndsAt,
                'current_period_start' => $currentPeriodStart,
                'current_period_end' => $currentPeriodEnd,
                'amount' => $plan->price,
                'currency' => 'BRL',
                'metadata' => [
                    'created_by' => 'seeder',
                    'source' => 'initial_setup',
                ],
            ]);

            // Criar alguns pagamentos para assinaturas ativas
            if ($status === 'active') {
                $paymentsCount = rand(1, 6); // 1 a 6 pagamentos
                
                for ($i = 0; $i < $paymentsCount; $i++) {
                    $paymentDate = $currentPeriodStart->copy()->addMonths($i);
                    
                    if ($paymentDate->isPast()) {
                        $paymentStatus = rand(1, 10) <= 9 ? 'paid' : 'failed'; // 90% pagos
                        $paymentMethods = ['credit_card', 'debit_card', 'pix', 'boleto'];
                        
                        Payment::create([
                            'company_subscription_id' => $subscription->id,
                            'payment_id' => 'PAY_' . strtoupper(uniqid()),
                            'status' => $paymentStatus,
                            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                            'amount' => $plan->price,
                            'currency' => 'BRL',
                            'paid_at' => $paymentStatus === 'paid' ? $paymentDate : null,
                            'due_date' => $paymentDate,
                            'gateway' => 'mercadopago',
                            'gateway_payment_id' => 'MP_' . rand(100000, 999999),
                            'gateway_response' => [
                                'transaction_id' => rand(100000, 999999),
                                'status' => $paymentStatus,
                                'created_at' => $paymentDate->toISOString(),
                            ],
                        ]);
                    }
                }
            }

            // Criar alguns pagamentos pendentes
            if ($status === 'active' && rand(1, 3) === 1) { // 33% de chance
                Payment::create([
                    'company_subscription_id' => $subscription->id,
                    'payment_id' => 'PAY_' . strtoupper(uniqid()),
                    'status' => 'pending',
                    'payment_method' => 'boleto',
                    'amount' => $plan->price,
                    'currency' => 'BRL',
                    'due_date' => $now->copy()->addDays(rand(1, 10)),
                    'gateway' => 'mercadopago',
                    'gateway_payment_id' => 'MP_' . rand(100000, 999999),
                ]);
            }
        }

        $this->command->info('Assinaturas e pagamentos de exemplo criados com sucesso!');
    }
}
