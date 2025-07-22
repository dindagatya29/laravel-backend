<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kpi;
use App\Models\User;

class KpiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user or create one
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }

        // Sample KPI categories
        $categories = [
            'Sales',
            'Marketing',
            'Customer Service',
            'Product Development',
            'Operations',
            'Finance',
            'Human Resources',
            'Quality Assurance',
            'Research & Development',
            'General'
        ];

        // Sample KPIs
        $kpis = [
            [
                'name' => 'Monthly Sales Revenue',
                'description' => 'Track monthly sales revenue to ensure business growth targets are met',
                'category' => 'Sales',
                'unit' => '$',
                'target_value' => 100000,
                'current_value' => 85000,
                'baseline_value' => 75000,
                'frequency' => 'monthly',
                'direction' => 'increase',
                'status' => 'active',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31'
            ],
            [
                'name' => 'Customer Satisfaction Score',
                'description' => 'Measure customer satisfaction through surveys and feedback',
                'category' => 'Customer Service',
                'unit' => '%',
                'target_value' => 90,
                'current_value' => 87,
                'baseline_value' => 85,
                'frequency' => 'monthly',
                'direction' => 'increase',
                'status' => 'active',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31'
            ],
            [
                'name' => 'Website Conversion Rate',
                'description' => 'Track website visitor to customer conversion rate',
                'category' => 'Marketing',
                'unit' => '%',
                'target_value' => 3.5,
                'current_value' => 3.2,
                'baseline_value' => 2.8,
                'frequency' => 'weekly',
                'direction' => 'increase',
                'status' => 'active',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31'
            ],
            [
                'name' => 'Product Development Cycle Time',
                'description' => 'Time from concept to market launch for new products',
                'category' => 'Product Development',
                'unit' => 'days',
                'target_value' => 90,
                'current_value' => 95,
                'baseline_value' => 120,
                'frequency' => 'quarterly',
                'direction' => 'decrease',
                'status' => 'active',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31'
            ],
            [
                'name' => 'Employee Retention Rate',
                'description' => 'Percentage of employees who stay with the company',
                'category' => 'Human Resources',
                'unit' => '%',
                'target_value' => 95,
                'current_value' => 92,
                'baseline_value' => 90,
                'frequency' => 'quarterly',
                'direction' => 'increase',
                'status' => 'active',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31'
            ],
            [
                'name' => 'Operational Efficiency',
                'description' => 'Overall operational efficiency score',
                'category' => 'Operations',
                'unit' => '%',
                'target_value' => 85,
                'current_value' => 82,
                'baseline_value' => 80,
                'frequency' => 'monthly',
                'direction' => 'increase',
                'status' => 'active',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31'
            ],
            [
                'name' => 'Profit Margin',
                'description' => 'Net profit margin percentage',
                'category' => 'Finance',
                'unit' => '%',
                'target_value' => 25,
                'current_value' => 23,
                'baseline_value' => 20,
                'frequency' => 'monthly',
                'direction' => 'increase',
                'status' => 'active',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31'
            ],
            [
                'name' => 'Bug Resolution Time',
                'description' => 'Average time to resolve software bugs',
                'category' => 'Quality Assurance',
                'unit' => 'hours',
                'target_value' => 24,
                'current_value' => 28,
                'baseline_value' => 48,
                'frequency' => 'weekly',
                'direction' => 'decrease',
                'status' => 'active',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31'
            ]
        ];

        // Create KPIs
        foreach ($kpis as $kpiData) {
            Kpi::create([
                'user_id' => $user->id,
                'name' => $kpiData['name'],
                'description' => $kpiData['description'],
                'category' => $kpiData['category'],
                'unit' => $kpiData['unit'],
                'target_value' => $kpiData['target_value'],
                'current_value' => $kpiData['current_value'],
                'baseline_value' => $kpiData['baseline_value'],
                'frequency' => $kpiData['frequency'],
                'direction' => $kpiData['direction'],
                'status' => $kpiData['status'],
                'start_date' => $kpiData['start_date'],
                'end_date' => $kpiData['end_date']
            ]);
        }

        $this->command->info('KPI seeder completed successfully!');
        $this->command->info('Created ' . count($kpis) . ' sample KPIs');
    }
}
