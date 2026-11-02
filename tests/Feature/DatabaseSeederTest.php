<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test as Exam;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_repeatable_and_keeps_demo_exams_available_today(): void
    {
        $this->seed(DatabaseSeeder::class);
        $countsAfterFirstRun = $this->seededRecordCounts();

        $this->seed(DatabaseSeeder::class);
        $countsAfterSecondRun = $this->seededRecordCounts();

        $this->assertSame($countsAfterFirstRun, $countsAfterSecondRun);
        $this->assertSame(2, Exam::whereDate('tanggal', now()->toDateString())->count());
        $this->assertSame(5, User::count());
    }

    private function seededRecordCounts(): array
    {
        return [
            User::count(),
            Exam::count(),
            Question::count(),
            Attempt::count(),
            Answer::count(),
        ];
    }
}
