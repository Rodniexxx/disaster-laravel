<?php

namespace Database\Seeders;

use App\Models\Scenario;
use Illuminate\Database\Seeder;

/**
 * Seeds the four canonical MANGNO disaster categories. Uses firstOrCreate by
 * slug so it is safe to run against an existing database without overwriting
 * administrator-edited content.
 */
class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = [
            [
                'slug' => 'typhoon',
                'title' => 'Typhoon',
                'difficulty' => 'Hard',
                'description' => 'Prepare for and respond to a tropical cyclone, from early warnings to post-storm safety.',
                'instructions' => 'Secure your home, prepare an emergency kit, and know your evacuation route.',
                'learning_objectives' => 'Recognise typhoon warnings and take protective action before, during and after landfall.',
                'safety_info' => 'Stay indoors, away from windows, and never walk through flood water or downed power lines.',
                'completion_info' => 'You have completed the typhoon preparedness scenario.',
            ],
            [
                'slug' => 'earthquake',
                'title' => 'Earthquake',
                'difficulty' => 'Medium',
                'description' => 'Learn the correct actions to take during a strong earthquake.',
                'instructions' => 'Drop to the ground, take cover under sturdy furniture, and hold on.',
                'learning_objectives' => 'Apply Drop, Cover and Hold On and identify safe spots indoors and outdoors.',
                'safety_info' => 'Beware of aftershocks and falling debris. Check for gas leaks and structural damage.',
                'completion_info' => 'You have completed the earthquake preparedness scenario.',
            ],
            [
                'slug' => 'volcano',
                'title' => 'Volcanic Eruption',
                'difficulty' => 'Hard',
                'description' => 'Understand volcanic hazards and how to stay safe during an eruption.',
                'instructions' => 'Follow official evacuation orders and protect yourself from ashfall.',
                'learning_objectives' => 'Identify eruption warning signs and appropriate protective measures.',
                'safety_info' => 'Wear masks and goggles, avoid river valleys, and stay clear of ash deposits.',
                'completion_info' => 'You have completed the volcanic eruption preparedness scenario.',
            ],
            [
                'slug' => 'fire',
                'title' => 'Fire',
                'difficulty' => 'Easy',
                'description' => 'Learn fire prevention and safe evacuation from a burning building.',
                'instructions' => 'Stay low, feel doors before opening, and use the nearest safe exit.',
                'learning_objectives' => 'Practise safe evacuation and basic fire-response behaviour.',
                'safety_info' => 'Never re-enter a burning building and call emergency services immediately.',
                'completion_info' => 'You have completed the fire preparedness scenario.',
            ],
        ];

        foreach ($scenarios as $scenario) {
            Scenario::query()->firstOrCreate(
                ['slug' => $scenario['slug']],
                $scenario + ['status' => 'active'],
            );
        }
    }
}
