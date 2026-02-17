<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\CleaningSession;
use App\Models\Property;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SpecificHousekeeperSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'owner']);
        Role::firstOrCreate(['name' => 'housekeeper']);

        // 1. Create Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        // 2. Create Owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Property Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $owner->syncRoles(['owner']);

        // 3. Create Housekeeper
        $hk = User::firstOrCreate(
            ['email' => 'housekeeper@example.com'],
            [
                'name' => 'Sample Housekeeper',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $hk->syncRoles(['housekeeper']);

        // 4. Create a specific Property for the demo
        $property = Property::firstOrCreate(
            ['name' => 'Beautiful Beach Villa'],
            [
                'address' => '123 Ocean Drive, Miami, FL',
                'owner_id' => $owner->id,
                'latitude' => 25.7617,
                'longitude' => -80.1918,
            ]
        );

        // 5. Create Rooms and attach to Property
        $rooms = ['Master Bedroom', 'Luxury Kitchen', 'Outdoor Pool Area'];
        $createdRooms = [];
        foreach ($rooms as $roomName) {
            $room = Room::firstOrCreate(['name' => $roomName], ['is_default' => true]);
            $createdRooms[] = $room;
            $property->rooms()->syncWithoutDetaching([$room->id => ['sort_order' => count($createdRooms)]]);
        }

        // 6. Create Tasks and attach to Rooms
        $tasks = [
            'Wipe all surfaces',
            'Vacuum and mop floors',
            'Change bed linens',
            'Disinfect door handles',
            'Check for guest damages'
        ];
        foreach ($createdRooms as $room) {
            foreach ($tasks as $idx => $taskName) {
                $task = Task::firstOrCreate(['name' => $taskName], ['type' => 'room', 'is_default' => true]);
                $room->tasks()->syncWithoutDetaching([$task->id => [
                    'sort_order' => $idx + 1,
                    'visible_to_owner' => true,
                    'visible_to_housekeeper' => true
                ]]);
            }
        }

        // 7. Create an ACTIVE Cleaning Session for TODAY
        $session = CleaningSession::create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'housekeeper_id' => $hk->id,
            'scheduled_date' => now()->format('Y-m-d'),
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30),
        ]);

        // 8. Create Checklist Items for this session
        foreach ($createdRooms as $room) {
            $roomTasks = $room->tasks()->get();
            foreach ($roomTasks as $idx => $task) {
                // Mark some as checked to make it look real
                $isChecked = ($idx < 2); 
                ChecklistItem::create([
                    'session_id' => $session->id,
                    'room_id' => $room->id,
                    'task_id' => $task->id,
                    'user_id' => $hk->id,
                    'checked' => $isChecked,
                    'checked_at' => $isChecked ? now()->subMinutes(15) : null,
                    'note' => $isChecked ? 'Completed early' : null,
                ]);
            }
        }

        $this->command->info('Demo scenario for housekeeper@example.com created successfully!');
    }
}
