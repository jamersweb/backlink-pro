<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\State;
use App\Models\City;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Countries
        $us = Country::firstOrCreate(['name' => 'United States']);
        $uk = Country::firstOrCreate(['name' => 'United Kingdom']);
        $ca = Country::firstOrCreate(['name' => 'Canada']);
        
        // Create States for US
        $california = State::firstOrCreate(['country_id' => $us->id, 'name' => 'California']);
        $newYork = State::firstOrCreate(['country_id' => $us->id, 'name' => 'New York']);
        $texas = State::firstOrCreate(['country_id' => $us->id, 'name' => 'Texas']);
        
        // Create Cities for California
        City::firstOrCreate(['state_id' => $california->id, 'name' => 'Los Angeles']);
        City::firstOrCreate(['state_id' => $california->id, 'name' => 'San Francisco']);
        City::firstOrCreate(['state_id' => $california->id, 'name' => 'San Diego']);
        
        // Create Cities for New York
        City::firstOrCreate(['state_id' => $newYork->id, 'name' => 'New York City']);
        City::firstOrCreate(['state_id' => $newYork->id, 'name' => 'Buffalo']);
        
        // Create Cities for Texas
        City::firstOrCreate(['state_id' => $texas->id, 'name' => 'Houston']);
        City::firstOrCreate(['state_id' => $texas->id, 'name' => 'Dallas']);
        
        $this->command->info('Sample locations seeded successfully!');
    }
}
