<?php

namespace Tests\Feature\Modules;

use App\Models\Contact;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_update_and_delete_contact_with_optional_venue(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $venue = Venue::create([
            'user_id' => $user->id,
            'name' => 'Arena Teste',
            'status' => 'undefined',
        ]);

        $this->actingAs($user)
            ->post(route('contacts.store'), [
                'name' => 'Maria Contato',
                'email' => 'maria@example.com',
                'phone' => '(11) 9999-1111',
                'whatsapp' => '(11) 99999-1111',
                'description' => 'Produtora local',
                'venue_id' => $venue->id,
            ])
            ->assertRedirect(route('contacts.index'));

        $contact = Contact::query()->firstOrFail();
        $this->assertSame('Maria Contato', $contact->name);
        $this->assertSame($venue->id, $contact->venue_id);

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), [
                'name' => 'Maria Atualizada',
                'email' => 'maria.nova@example.com',
                'phone' => '(11) 9888-2222',
                'whatsapp' => '',
                'description' => 'Contato atualizado',
                'venue_id' => null,
            ])
            ->assertRedirect(route('contacts.index'));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Maria Atualizada',
            'venue_id' => null,
        ]);

        $this->actingAs($user)
            ->delete(route('contacts.destroy', $contact))
            ->assertRedirect(route('contacts.index'));

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_venue_store_and_update_sync_contact_without_duplicates(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('venues.store'), [
                'name' => 'Local Sync',
                'status' => 'undefined',
                'contact_name' => 'Carlos',
                'phone' => '(31) 9999-8888',
                'email' => 'carlos@localsync.com',
            ])
            ->assertRedirect(route('venues.index'));

        $venue = Venue::query()->where('name', 'Local Sync')->firstOrFail();
        $contact = Contact::query()->where('venue_id', $venue->id)->firstOrFail();

        $this->assertSame('Carlos (Local Sync)', $contact->name);
        $this->assertSame('(31) 9999-8888', $contact->phone);

        $this->actingAs($user)
            ->put(route('venues.update', $venue), [
                'name' => 'Local Sync',
                'status' => 'undefined',
                'contact_name' => '',
                'phone' => '(31) 9777-6666',
                'email' => 'novo@localsync.com',
            ])
            ->assertRedirect(route('venues.index'));

        $this->assertSame(1, Contact::query()->where('venue_id', $venue->id)->count());

        $updatedContact = Contact::query()->where('venue_id', $venue->id)->firstOrFail();
        $this->assertSame('Local Sync', $updatedContact->name);
        $this->assertSame('(31) 9777-6666', $updatedContact->phone);
        $this->assertSame('novo@localsync.com', $updatedContact->email);
    }
}
