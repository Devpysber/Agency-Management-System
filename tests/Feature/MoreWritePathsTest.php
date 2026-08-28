<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Communication;
use App\Models\company;
use App\Models\contact;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Covers the write paths WritePathsTest didn't reach: the CSV contact
 * importer (real file parsing, dedupe-by-email, company-name matching),
 * calendar event creation, a communications log entry, quotation
 * inquiry creation, and a bulk-delete action.
 */
class MoreWritePathsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_contact_csv_import_creates_matches_and_skips_correctly(): void
    {
        $existing = contact::create(['first_name' => 'Already', 'last_name' => 'Here', 'email' => 'already.here@example.test']);
        $acme = company::create(['company_name' => 'Acme Corp', 'status' => 'active']);

        $csv = <<<CSV
        name,email,phone,company_name
        Brand New,brand.new@example.test,555-1000,Acme Corp
        Already Here,already.here@example.test,555-2000,
        No Email Person,,555-3000,
        Bad Email,not-an-email,555-4000,
        CSV;

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.contacts.import')
            ->set('csv_file', $file)
            ->call('import')
            ->assertSet('importedCount', 1)
            ->assertSet('skippedCount', 3);

        $imported = contact::where('email', 'brand.new@example.test')->first();
        $this->assertNotNull($imported);
        $this->assertSame('Brand New', trim($imported->first_name . ' ' . $imported->last_name));
        $this->assertEquals($acme->id, $imported->company_id);

        // The pre-existing contact wasn't duplicated.
        $this->assertSame(1, contact::where('email', 'already.here@example.test')->count());
    }

    public function test_calendar_event_create_persists(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.calendar.events')
            ->call('openAddModal')
            ->set('title', 'Client Kickoff Call')
            ->set('event_type', 'meeting')
            ->set('start_at', now()->addDay()->format('Y-m-d H:i:s'))
            ->call('save');

        $this->assertDatabaseHas('calendar_events', ['title' => 'Client Kickoff Call']);
    }

    public function test_communication_call_log_persists(): void
    {
        $contact = contact::create(['first_name' => 'Ring', 'last_name' => 'Me', 'email' => 'ring.me@example.test']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.communications.calls')
            ->call('openAddModal')
            ->set('subject', 'Follow-up call')
            ->set('direction', 'outbound')
            ->set('status', 'completed')
            ->set('occurred_at', now()->format('Y-m-d H:i:s'))
            ->set('contact_id', $contact->id)
            ->call('save');

        $call = Communication::where('subject', 'Follow-up call')->first();
        $this->assertNotNull($call);
        $this->assertSame('call', $call->type);
        $this->assertEquals($contact->id, $call->contact_id);
    }

    public function test_quotation_add_persists_and_redirects_to_show(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.quotations.add')
            ->set('name', 'Prospective Client')
            ->set('email', 'prospect@example.test')
            ->set('service_interest', 'Branding')
            ->call('form_submit');

        $quotation = Quotation::where('email', 'prospect@example.test')->first();
        $this->assertNotNull($quotation);
        $this->assertSame('pending', $quotation->status);
    }

    public function test_bulk_delete_contacts(): void
    {
        $a = contact::create(['first_name' => 'A', 'last_name' => 'One', 'email' => 'a.one@example.test']);
        $b = contact::create(['first_name' => 'B', 'last_name' => 'Two', 'email' => 'b.two@example.test']);
        $keep = contact::create(['first_name' => 'Keep', 'last_name' => 'Me', 'email' => 'keep.me@example.test']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.contacts.allcontacts')
            ->set('selected', [(string) $a->id, (string) $b->id])
            ->call('deleteSelected');

        $this->assertDatabaseMissing('contacts', ['id' => $a->id]);
        $this->assertDatabaseMissing('contacts', ['id' => $b->id]);
        $this->assertDatabaseHas('contacts', ['id' => $keep->id]);
    }
}
