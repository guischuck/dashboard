<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'description' => 'Plano ideal para escritórios pequenos que estão começando.',
                'price' => 97.00,
                'billing_cycle' => 'monthly',
                'max_users' => 3,
                'max_cases' => 50,
                'features' => [
                    'basic_templates',
                    'case_management',
                    'document_storage',
                    'email_support',
                ],
                'is_active' => true,
                'is_featured' => false,
                'trial_days' => 30,
                'sort_order' => 1,
            ],
            [
                'name' => 'Profissional',
                'slug' => 'profissional',
                'description' => 'Plano completo para escritórios em crescimento.',
                'price' => 197.00,
                'billing_cycle' => 'monthly',
                'max_users' => 10,
                'max_cases' => 200,
                'features' => [
                    'basic_templates',
                    'custom_templates',
                    'case_management',
                    'document_storage',
                    'workflow_automation',
                    'advanced_reports',
                    'priority_support',
                ],
                'is_active' => true,
                'is_featured' => true,
                'trial_days' => 30,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solução completa para grandes escritórios e departamentos jurídicos.',
                'price' => 397.00,
                'billing_cycle' => 'monthly',
                'max_users' => null, // Ilimitado
                'max_cases' => null, // Ilimitado
                'features' => [
                    'unlimited_users',
                    'unlimited_cases',
                    'basic_templates',
                    'custom_templates',
                    'case_management',
                    'document_storage',
                    'workflow_automation',
                    'advanced_reports',
                    'document_ai',
                    'api_access',
                    'white_label',
                    'priority_support',
                    'dedicated_support',
                ],
                'is_active' => true,
                'is_featured' => false,
                'trial_days' => 30,
                'sort_order' => 3,
            ],
            [
                'name' => 'Básico Anual',
                'slug' => 'basico-anual',
                'description' => 'Plano básico com desconto anual.',
                'price' => 970.00, // 10 meses pelo preço de 12
                'billing_cycle' => 'annual',
                'max_users' => 3,
                'max_cases' => 50,
                'features' => [
                    'basic_templates',
                    'case_management',
                    'document_storage',
                    'email_support',
                ],
                'is_active' => true,
                'is_featured' => false,
                'trial_days' => 30,
                'sort_order' => 4,
            ],
            [
                'name' => 'Profissional Anual',
                'slug' => 'profissional-anual',
                'description' => 'Plano profissional com desconto anual.',
                'price' => 1970.00, // 10 meses pelo preço de 12
                'billing_cycle' => 'annual',
                'max_users' => 10,
                'max_cases' => 200,
                'features' => [
                    'basic_templates',
                    'custom_templates',
                    'case_management',
                    'document_storage',
                    'workflow_automation',
                    'advanced_reports',
                    'priority_support',
                ],
                'is_active' => true,
                'is_featured' => false,
                'trial_days' => 30,
                'sort_order' => 5,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}
