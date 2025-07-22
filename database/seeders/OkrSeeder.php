<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Okr;
use App\Models\KeyResult;
use App\Models\User;

class OkrSeeder extends Seeder
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

        // Sample OKRs with key results
        $okrs = [
            [
                'objective' => 'Increase Market Share by 25%',
                'description' => 'Expand our market presence and capture more customers in the target market',
                'category' => 'Business Growth',
                'type' => 'company',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'key_results' => [
                    [
                        'title' => 'Increase Sales Revenue',
                        'description' => 'Achieve 30% increase in total sales revenue',
                        'unit' => '$',
                        'target_value' => 1500000,
                        'current_value' => 1200000,
                        'baseline_value' => 1000000,
                        'direction' => 'increase',
                        'weight' => 3
                    ],
                    [
                        'title' => 'Acquire New Customers',
                        'description' => 'Add 500 new customers to our database',
                        'unit' => 'customers',
                        'target_value' => 500,
                        'current_value' => 350,
                        'baseline_value' => 200,
                        'direction' => 'increase',
                        'weight' => 2
                    ],
                    [
                        'title' => 'Improve Customer Retention',
                        'description' => 'Increase customer retention rate to 95%',
                        'unit' => '%',
                        'target_value' => 95,
                        'current_value' => 92,
                        'baseline_value' => 90,
                        'direction' => 'increase',
                        'weight' => 2
                    ]
                ]
            ],
            [
                'objective' => 'Launch New Product Successfully',
                'description' => 'Successfully develop and launch a new product in the market',
                'category' => 'Product Development',
                'type' => 'team',
                'start_date' => '2025-01-01',
                'end_date' => '2025-06-30',
                'key_results' => [
                    [
                        'title' => 'Complete Product Development',
                        'description' => 'Finish product development within 3 months',
                        'unit' => 'days',
                        'target_value' => 90,
                        'current_value' => 75,
                        'baseline_value' => 0,
                        'direction' => 'decrease',
                        'weight' => 3
                    ],
                    [
                        'title' => 'Achieve Beta Testing',
                        'description' => 'Complete beta testing with 100 users',
                        'unit' => 'users',
                        'target_value' => 100,
                        'current_value' => 80,
                        'baseline_value' => 0,
                        'direction' => 'increase',
                        'weight' => 2
                    ],
                    [
                        'title' => 'Meet Quality Standards',
                        'description' => 'Achieve 95% quality score in testing',
                        'unit' => '%',
                        'target_value' => 95,
                        'current_value' => 92,
                        'baseline_value' => 85,
                        'direction' => 'increase',
                        'weight' => 2
                    ]
                ]
            ],
            [
                'objective' => 'Improve Team Productivity',
                'description' => 'Enhance team efficiency and productivity across all departments',
                'category' => 'Operations',
                'type' => 'team',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'key_results' => [
                    [
                        'title' => 'Reduce Project Delivery Time',
                        'description' => 'Decrease average project delivery time by 20%',
                        'unit' => '%',
                        'target_value' => 20,
                        'current_value' => 15,
                        'baseline_value' => 0,
                        'direction' => 'increase',
                        'weight' => 3
                    ],
                    [
                        'title' => 'Increase Team Satisfaction',
                        'description' => 'Improve team satisfaction score to 4.5/5',
                        'unit' => 'score',
                        'target_value' => 4.5,
                        'current_value' => 4.2,
                        'baseline_value' => 4.0,
                        'direction' => 'increase',
                        'weight' => 2
                    ],
                    [
                        'title' => 'Reduce Operational Costs',
                        'description' => 'Decrease operational costs by 15%',
                        'unit' => '%',
                        'target_value' => 15,
                        'current_value' => 10,
                        'baseline_value' => 0,
                        'direction' => 'increase',
                        'weight' => 2
                    ]
                ]
            ],
            [
                'objective' => 'Enhance Customer Experience',
                'description' => 'Improve overall customer experience and satisfaction',
                'category' => 'Customer Service',
                'type' => 'company',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'key_results' => [
                    [
                        'title' => 'Improve Response Time',
                        'description' => 'Reduce customer support response time to under 2 hours',
                        'unit' => 'hours',
                        'target_value' => 2,
                        'current_value' => 3,
                        'baseline_value' => 6,
                        'direction' => 'decrease',
                        'weight' => 3
                    ],
                    [
                        'title' => 'Increase Customer Satisfaction',
                        'description' => 'Achieve 95% customer satisfaction score',
                        'unit' => '%',
                        'target_value' => 95,
                        'current_value' => 92,
                        'baseline_value' => 88,
                        'direction' => 'increase',
                        'weight' => 3
                    ],
                    [
                        'title' => 'Reduce Customer Complaints',
                        'description' => 'Decrease customer complaints by 30%',
                        'unit' => '%',
                        'target_value' => 30,
                        'current_value' => 20,
                        'baseline_value' => 0,
                        'direction' => 'increase',
                        'weight' => 2
                    ]
                ]
            ],
            [
                'objective' => 'Develop Professional Skills',
                'description' => 'Improve individual professional skills and knowledge',
                'category' => 'Personal Development',
                'type' => 'individual',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'key_results' => [
                    [
                        'title' => 'Complete Online Courses',
                        'description' => 'Complete 5 professional development courses',
                        'unit' => 'courses',
                        'target_value' => 5,
                        'current_value' => 3,
                        'baseline_value' => 0,
                        'direction' => 'increase',
                        'weight' => 2
                    ],
                    [
                        'title' => 'Obtain Certification',
                        'description' => 'Obtain 2 professional certifications',
                        'unit' => 'certifications',
                        'target_value' => 2,
                        'current_value' => 1,
                        'baseline_value' => 0,
                        'direction' => 'increase',
                        'weight' => 3
                    ],
                    [
                        'title' => 'Improve Performance Rating',
                        'description' => 'Achieve performance rating of 4.5/5',
                        'unit' => 'rating',
                        'target_value' => 4.5,
                        'current_value' => 4.2,
                        'baseline_value' => 4.0,
                        'direction' => 'increase',
                        'weight' => 2
                    ]
                ]
            ]
        ];

        // Create OKRs with key results
        foreach ($okrs as $okrData) {
            $keyResults = $okrData['key_results'];
            unset($okrData['key_results']);

            $okr = Okr::create([
                'user_id' => $user->id,
                'objective' => $okrData['objective'],
                'description' => $okrData['description'],
                'category' => $okrData['category'],
                'type' => $okrData['type'],
                'start_date' => $okrData['start_date'],
                'end_date' => $okrData['end_date']
            ]);

            // Create key results for this OKR
            foreach ($keyResults as $krData) {
                KeyResult::create([
                    'okr_id' => $okr->id,
                    'title' => $krData['title'],
                    'description' => $krData['description'],
                    'unit' => $krData['unit'],
                    'target_value' => $krData['target_value'],
                    'current_value' => $krData['current_value'],
                    'baseline_value' => $krData['baseline_value'],
                    'direction' => $krData['direction'],
                    'weight' => $krData['weight']
                ]);
            }
        }

        $this->command->info('OKR seeder completed successfully!');
        $this->command->info('Created ' . count($okrs) . ' sample OKRs with key results');
    }
}
