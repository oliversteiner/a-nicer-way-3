<?php

namespace Tests\Feature;

use App\Enums\CharacterPose;
use App\Models\BoardAsset;
use App\Models\Character;
use App\Models\CharacterImage;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CharacterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(BoardAsset::DISK);
    }

    private function imageFor(Character $character, string $pose = 'idle'): CharacterImage
    {
        $path = UploadedFile::fake()->image('x.png')->store("journeys/{$character->journey_id}/characters", BoardAsset::DISK);

        return CharacterImage::factory()->for($character)->create(['pose' => $pose, 'path' => $path]);
    }

    private function upload(Character $character, string $pose, UploadedFile $file, ?User $as = null)
    {
        return $this->actingAs($as ?? $character->journey->user)
            ->post("/editor/characters/{$character->id}/images", ['pose' => $pose, 'image' => $file]);
    }

    public function test_owner_can_add_characters_in_order(): void
    {
        $journey = Journey::factory()->create();

        $this->actingAs($journey->user)->post("/editor/journeys/{$journey->id}/characters", ['name' => 'Kevin'])->assertRedirect();
        $this->actingAs($journey->user)->post("/editor/journeys/{$journey->id}/characters", ['name' => 'Bus']);

        $this->assertSame(['Kevin' => 1, 'Bus' => 2], $journey->characters()->pluck('position', 'name')->all());
    }

    public function test_name_is_required_and_limited(): void
    {
        $journey = Journey::factory()->create();

        $this->actingAs($journey->user)->post("/editor/journeys/{$journey->id}/characters", ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($journey->user)->post("/editor/journeys/{$journey->id}/characters", ['name' => str_repeat('a', 101)])->assertSessionHasErrors('name');
        $this->assertSame(0, $journey->characters()->count());
    }

    public function test_owner_can_rename_and_delete_a_character(): void
    {
        $character = Character::factory()->create(['name' => 'Alt']);

        $this->actingAs($character->journey->user)->put("/editor/characters/{$character->id}", ['name' => 'Neu'])->assertRedirect();
        $this->assertSame('Neu', $character->fresh()->name);

        $this->actingAs($character->journey->user)->delete("/editor/characters/{$character->id}")->assertRedirect();
        $this->assertModelMissing($character);
    }

    public function test_other_users_cannot_manage_characters(): void
    {
        $character = Character::factory()->create();
        $image = $this->imageFor($character);
        $other = User::factory()->create();

        $this->actingAs($other)->post("/editor/journeys/{$character->journey_id}/characters", ['name' => 'X'])->assertForbidden();
        $this->actingAs($other)->put("/editor/characters/{$character->id}", ['name' => 'X'])->assertForbidden();
        $this->actingAs($other)->delete("/editor/characters/{$character->id}")->assertForbidden();
        $this->upload($character, 'go', UploadedFile::fake()->image('a.png'), $other)->assertForbidden();
        $this->actingAs($other)->delete("/editor/character-images/{$image->id}")->assertForbidden();
        $this->assertModelExists($character);
        $this->assertModelExists($image);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $character = Character::factory()->create();

        $this->put("/editor/characters/{$character->id}", ['name' => 'X'])->assertRedirect('/login');
    }

    public function test_an_image_can_be_uploaded_per_pose(): void
    {
        $character = Character::factory()->create();

        $this->upload($character, 'idle', UploadedFile::fake()->image('a.png'))->assertRedirect();
        $this->upload($character, 'go', UploadedFile::fake()->image('b.jpg'));

        $images = $character->images()->get();
        $this->assertEqualsCanonicalizing([CharacterPose::Idle, CharacterPose::Go], $images->pluck('pose')->all());
        $images->each(fn ($image) => Storage::disk(BoardAsset::DISK)->assertExists($image->path));
    }

    public function test_uploading_the_same_pose_again_replaces_the_image_and_its_file(): void
    {
        $character = Character::factory()->create();
        $old = $this->imageFor($character, 'idle');

        $this->upload($character, 'idle', UploadedFile::fake()->image('new.png'));

        $this->assertModelMissing($old);
        Storage::disk(BoardAsset::DISK)->assertMissing($old->path);
        $this->assertSame(1, $character->images()->count());
    }

    public function test_svg_is_accepted_and_invalid_uploads_are_rejected(): void
    {
        $character = Character::factory()->create();
        $svg = UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

        $this->upload($character, 'idle', $svg)->assertSessionHasNoErrors();
        $this->upload($character, 'go', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->assertSessionHasErrors('image');
        $this->upload($character, 'go', UploadedFile::fake()->image('big.png')->size(5121))->assertSessionHasErrors('image');
        $this->upload($character, 'fly', UploadedFile::fake()->image('a.png'))->assertSessionHasErrors('pose');
        $this->assertSame(1, $character->images()->count());
    }

    public function test_deleting_an_image_removes_its_file(): void
    {
        $image = $this->imageFor(Character::factory()->create());

        $this->actingAs($image->character->journey->user)->delete("/editor/character-images/{$image->id}")->assertRedirect();

        $this->assertModelMissing($image);
        Storage::disk(BoardAsset::DISK)->assertMissing($image->path);
    }

    public function test_deleting_a_character_a_journey_or_a_user_removes_the_files(): void
    {
        $first = $this->imageFor(Character::factory()->create());
        $second = $this->imageFor(Character::factory()->create());
        $third = $this->imageFor(Character::factory()->create());

        $first->character->delete();
        $second->character->journey->delete();
        $third->character->journey->user->delete();

        foreach ([$first, $second, $third] as $image) {
            Storage::disk(BoardAsset::DISK)->assertMissing($image->path);
        }
        $this->assertDatabaseCount('character_images', 0);
    }

    public function test_images_of_published_journeys_are_public_with_hardening_headers(): void
    {
        $image = $this->imageFor(Character::factory()->create());

        $this->get("/character-images/{$image->id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
    }

    public function test_images_of_drafts_are_only_visible_to_the_owner(): void
    {
        $journey = Journey::factory()->draft()->create();
        $image = $this->imageFor(Character::factory()->for($journey)->create());

        $this->get("/character-images/{$image->id}")->assertNotFound();
        $this->actingAs(User::factory()->create())->get("/character-images/{$image->id}")->assertNotFound();
        $this->actingAs($journey->user)->get("/character-images/{$image->id}")->assertOk();
    }

    public function test_a_missing_file_returns_404(): void
    {
        $image = CharacterImage::factory()->create(['path' => 'journeys/1/characters/gone.png']);

        $this->get("/character-images/{$image->id}")->assertNotFound();
    }

    public function test_public_page_lists_characters_with_pose_urls(): void
    {
        $character = Character::factory()->create(['name' => 'Kevin', 'position' => 1]);
        $idle = $this->imageFor($character, 'idle');
        $go = $this->imageFor($character, 'go');

        $this->get("/journeys/{$character->journey->slug}")->assertInertia(fn (Assert $page) => $page
            ->where('journey.characters.0.name', 'Kevin')
            ->where('journey.characters.0.images.idle', route('character-images.show', $idle))
            ->where('journey.characters.0.images.go', route('character-images.show', $go))
            ->missing('journey.characters.0.images.pause'));
    }

    public function test_editor_page_lists_characters_with_image_ids(): void
    {
        $character = Character::factory()->create(['name' => 'Bus']);
        $image = $this->imageFor($character, 'pause');

        $this->actingAs($character->journey->user)->get("/editor/journeys/{$character->journey_id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('journey.characters.0.name', 'Bus')
                ->where('journey.characters.0.images.0.id', $image->id)
                ->where('journey.characters.0.images.0.pose', 'pause')
                ->where('journey.characters.0.images.0.url', route('character-images.show', $image)));
    }

    public function test_the_demo_seeder_adds_placeholder_characters_once(): void
    {
        $this->seed(\Database\Seeders\JourneySeeder::class);
        $this->seed(\Database\Seeders\JourneySeeder::class);

        $this->assertSame(['Kevin', 'Gruppe', 'Bus'], Character::orderBy('position')->pluck('name')->all());
    }
}
