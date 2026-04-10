<?php

namespace Tests\Feature\Modules;

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentFileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_and_remove_content_files(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->createOne();

        $content = Content::create([
            'user_id' => $user->id,
            'title' => 'Conteudo com arquivo',
            'status' => 'queued',
        ]);

        $upload = UploadedFile::fake()->create('clip.mp4', 1024, 'video/mp4');

        $this->actingAs($user)
            ->post(route('contents.files.store', $content), ['file' => $upload])
            ->assertStatus(302);

        $file = \App\Models\ContentFile::where('content_id', $content->id)->firstOrFail();
        $this->assertTrue(Storage::disk('local')->exists($file->path));

        $this->actingAs($user)
            ->delete(route('contents.files.destroy', [$content, $file]))
            ->assertStatus(302);

        $this->assertFalse(Storage::disk('local')->exists($file->path));
        $this->assertDatabaseMissing('content_files', ['id' => $file->id]);
    }

    public function test_non_owner_can_upload_content_files_in_collaborative_mode(): void
    {
        Storage::fake('local');

        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $other */
        $other = User::factory()->createOne();

        $content = Content::create([
            'user_id' => $owner->id,
            'title' => 'Conteudo protegido',
            'status' => 'queued',
        ]);

        $upload = UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4');

        $this->actingAs($other)
            ->post(route('contents.files.store', $content), ['file' => $upload])
            ->assertStatus(302);

        $this->assertDatabaseCount('content_files', 1);
    }
}
