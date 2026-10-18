<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_classroom(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->postJson('/api/classrooms', [
                'name' => 'Kelas 10-A Bahasa Indonesia',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Kelas 10-A Bahasa Indonesia']);

        $this->assertDatabaseHas('classrooms', [
            'name' => 'Kelas 10-A Bahasa Indonesia',
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_student_cannot_create_classroom(): void
    {
        $student = User::factory()->student()->create([
            'api_token' => Str::random(60),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson('/api/classrooms', [
                'name' => 'Kelas Ilegal',
            ])->assertForbidden();
    }

    public function test_student_can_join_classroom_and_duplicate_join_is_safe(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $student = User::factory()->student()->create([
            'api_token' => Str::random(60),
        ]);

        $classroom = Classroom::query()->create([
            'name' => 'Kelas 11 IPA 1',
            'teacher_id' => $teacher->id,
            'code' => 'IPA101',
        ]);

        // Join with valid code
        $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson('/api/classrooms/join', [
                'code' => 'ipa101', // case-insensitive check
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Berhasil bergabung ke kelas Kelas 11 IPA 1.']);

        $this->assertDatabaseHas('classroom_user', [
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
        ]);

        // Joining again shouldn't fail with 500
        $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson('/api/classrooms/join', [
                'code' => 'IPA101',
            ])->assertOk();

        // Invalid code should return 404
        $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson('/api/classrooms/join', [
                'code' => 'INVALID99',
            ])->assertNotFound();
    }

    public function test_teacher_and_student_can_list_classrooms(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $student = User::factory()->student()->create([
            'api_token' => Str::random(60),
        ]);

        $classroom = Classroom::query()->create([
            'name' => 'Kelas 12 IPS 2',
            'teacher_id' => $teacher->id,
            'code' => 'IPS202',
        ]);
        $classroom->users()->attach($student->id);

        // Teacher list
        $teacherResponse = $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->getJson('/api/classrooms');

        $teacherResponse->assertOk()
            ->assertJsonFragment(['name' => 'Kelas 12 IPS 2'])
            ->assertJsonFragment(['users_count' => 1]);

        // Student list
        $studentResponse = $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->getJson('/api/classrooms');

        $studentResponse->assertOk()
            ->assertJsonFragment(['name' => 'Kelas 12 IPS 2']);
    }

    public function test_teacher_can_delete_classroom(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $classroom = Classroom::query()->create([
            'name' => 'Kelas Hapus',
            'teacher_id' => $teacher->id,
            'code' => 'DEL123',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->deleteJson("/api/classrooms/{$classroom->id}")
            ->assertOk();

        $this->assertDatabaseMissing('classrooms', [
            'id' => $classroom->id,
        ]);
    }
}
