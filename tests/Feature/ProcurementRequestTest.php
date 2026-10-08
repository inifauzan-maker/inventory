<?php

namespace Tests\Feature;

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class ProcurementRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_draft_with_exact_totals_and_identity_from_login(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);
        $payload = $this->payload();
        $payload['requester_id'] = 999;
        $payload['status'] = 'approved';
        $payload['estimated_total'] = '1';

        $this->actingAs($user)->post(route('pengajuan.store'), $payload)->assertSessionHasNoErrors();

        $proposal = ProcurementRequest::first();
        $this->assertSame($user->id, $proposal->requester_id);
        $this->assertSame($user->name, $proposal->requester_name);
        $this->assertSame('draft', $proposal->status);
        $this->assertSame('201.50', $proposal->estimated_total);
        $this->assertSame('201.50', $proposal->items->first()->subtotal);
        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('assets', 0);
        $this->get(route('pengajuan.show', $proposal))->assertSee('Kirim ke Keuangan')->assertSee('Cetak Surat / PDF');
        $this->get(route('pengajuan.edit', $proposal))->assertOk()->assertSee('Ubah Draf');
    }

    public function test_unknown_prices_remain_null_and_zero_remains_zero(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload();
        $payload['items'][] = array_replace($payload['items'][0], ['estimated_unit_price' => null]);
        $payload['items'][] = array_replace($payload['items'][0], ['estimated_unit_price' => '0']);
        $this->actingAs($user)->post(route('pengajuan.store'), $payload)->assertSessionHasNoErrors();

        $proposal = ProcurementRequest::first();
        $this->assertNull($proposal->estimated_total);
        $this->assertNull($proposal->items[1]->subtotal);
        $this->assertSame('0.00', $proposal->items[2]->subtotal);
    }

    public function test_finance_then_director_approve_and_record_separate_reviewers(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_STAFF]);
        $finance = User::factory()->create(['role' => User::ROLE_KEUANGAN]);
        $director = User::factory()->create(['role' => User::ROLE_DIREKSI]);
        $proposal = ProcurementRequest::factory()->for($owner, 'requester')->create();
        ProcurementRequestItem::factory()->for($proposal, 'procurementRequest')->create();

        $this->actingAs($owner)->post(route('pengajuan.submit', $proposal))->assertSessionHasNoErrors();
        $this->assertSame('pending_finance', $proposal->fresh()->status);
        $this->actingAs($finance)->get(route('pengajuan.index'))->assertSee($proposal->letter_number);
        $this->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'approved', 'notes' => 'Anggaran tersedia'])->assertSessionHasNoErrors();
        $this->assertSame('pending_director', $proposal->fresh()->status);
        $this->actingAs($director)->get(route('pengajuan.index'))->assertSee($proposal->letter_number);
        $this->post(route('pengajuan.review', [$proposal, 'director']), ['decision' => 'approved', 'notes' => 'Disetujui'])->assertSessionHasNoErrors();

        $this->assertSame('approved', $proposal->fresh()->status);
        $this->assertDatabaseCount('procurement_decisions', 2);
        $this->assertDatabaseHas('procurement_decisions', ['reviewer_id' => $finance->id, 'level' => 'finance', 'decision' => 'approved', 'version' => 1]);
        $this->assertDatabaseHas('procurement_decisions', ['reviewer_id' => $director->id, 'level' => 'director', 'decision' => 'approved', 'version' => 1]);
        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_director_cannot_bypass_finance_or_see_its_pending_queue(): void
    {
        $proposal = ProcurementRequest::factory()->submitted()->create();
        $director = User::factory()->create(['role' => User::ROLE_DIREKSI]);

        $this->actingAs($director)->post(route('pengajuan.review', [$proposal, 'director']), ['decision' => 'approved'])->assertForbidden();
        $this->get(route('pengajuan.index'))->assertDontSee($proposal->letter_number);
        $this->assertDatabaseCount('procurement_decisions', 0);
        $this->assertSame('pending_finance', $proposal->fresh()->status);
    }

    public function test_approver_cannot_approve_their_own_request_even_as_superadmin(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $proposal = ProcurementRequest::factory()->submitted()->for($owner, 'requester')->create();

        $this->actingAs($owner)->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'approved'])->assertForbidden();
        $this->assertFalse(Gate::forUser($owner)->allows('reviewFinance', $proposal));
        $this->assertDatabaseCount('procurement_decisions', 0);
    }

    public function test_same_superadmin_cannot_approve_both_levels(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $proposal = ProcurementRequest::factory()->submitted()->create();
        $this->actingAs($admin)->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'approved'])->assertSessionHasNoErrors();

        $this->post(route('pengajuan.review', [$proposal, 'director']), ['decision' => 'approved'])->assertForbidden();
        $this->assertSame('pending_director', $proposal->fresh()->status);
        $this->assertDatabaseCount('procurement_decisions', 1);
    }

    public function test_rejection_requires_reason_and_closes_request(): void
    {
        $proposal = ProcurementRequest::factory()->submitted()->create();
        $finance = User::factory()->create(['role' => User::ROLE_KEUANGAN]);

        $this->actingAs($finance)->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'rejected'])
            ->assertSessionHasErrors(['notes' => 'Alasan penolakan wajib diisi.']);
        $this->assertSame('pending_finance', $proposal->fresh()->status);
        $this->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'rejected', 'notes' => 'Anggaran belum tersedia'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $proposal->fresh()->status);
        $this->assertDatabaseHas('procurement_decisions', ['decision' => 'rejected', 'notes' => 'Anggaran belum tersedia']);
        $this->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($proposal->requester)->post(route('pengajuan.submit', $proposal))->assertSessionHasErrors('status');
    }

    public function test_alternatives_are_not_summed_and_finance_selects_only_owned_item(): void
    {
        $owner = User::factory()->create();
        $payload = $this->payload();
        $payload['selection_mode'] = 'alternatives';
        $payload['items'][] = array_replace($payload['items'][0], ['name' => 'Pilihan kedua', 'estimated_unit_price' => '250']);
        $this->actingAs($owner)->post(route('pengajuan.store'), $payload);
        $proposal = ProcurementRequest::first();
        $this->assertNull($proposal->estimated_total);
        $this->post(route('pengajuan.submit', $proposal));
        $finance = User::factory()->create(['role' => User::ROLE_KEUANGAN]);
        $unrelated = ProcurementRequestItem::factory()->create();

        $this->actingAs($finance)->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'approved', 'selected_item_id' => $unrelated->id])->assertSessionHasErrors('selected_item_id');
        $this->assertDatabaseCount('procurement_decisions', 0);
        $this->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'approved', 'selected_item_id' => $proposal->items->first()->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('procurement_decisions', ['selected_item_id' => $proposal->items->first()->id]);
        $this->assertSame('pending_director', $proposal->fresh()->status);
    }

    public function test_unrelated_user_cannot_read_edit_submit_or_download_request(): void
    {
        $proposal = ProcurementRequest::factory()->create();
        $stranger = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($stranger)->get(route('pengajuan.index'))->assertDontSee($proposal->letter_number);
        $this->get(route('pengajuan.show', $proposal))->assertForbidden();
        $this->get(route('pengajuan.edit', $proposal))->assertForbidden();
        $this->get(route('pengajuan.document', $proposal))->assertForbidden();
        $this->get(route('pengajuan.letter', $proposal))->assertForbidden();
        $this->post(route('pengajuan.submit', $proposal))->assertForbidden();
        $this->assertSame('draft', $proposal->fresh()->status);
    }

    public function test_finance_does_not_see_another_users_draft(): void
    {
        $proposal = ProcurementRequest::factory()->create();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KEUANGAN]))->get(route('pengajuan.index'))->assertDontSee($proposal->letter_number);
        $this->get(route('pengajuan.show', $proposal))->assertForbidden();
    }

    public function test_submitted_request_cannot_be_changed_by_owner_or_superadmin(): void
    {
        $proposal = ProcurementRequest::factory()->submitted()->create();
        $payload = $this->payload();
        $payload['version'] = 1;
        $this->actingAs($proposal->requester)->put(route('pengajuan.update', $proposal), $payload)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]))->put(route('pengajuan.update', $proposal), $payload)->assertForbidden();
        $this->assertSame('pending_finance', $proposal->fresh()->status);
    }

    public function test_draft_updates_advance_version_and_stale_edit_is_rejected(): void
    {
        $proposal = ProcurementRequest::factory()->create();
        $payload = $this->payload();
        $payload['version'] = 1;
        $this->actingAs($proposal->requester)->put(route('pengajuan.update', $proposal), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, $proposal->fresh()->version);
        $payload['subject'] = 'Stale change';
        $this->put(route('pengajuan.update', $proposal), $payload)->assertSessionHasErrors('version');
        $this->assertNotSame('Stale change', $proposal->fresh()->subject);
        $this->assertDatabaseCount('procurement_request_items', 1);
    }

    public function test_retry_creation_and_submission_do_not_duplicate_data(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload();
        $this->actingAs($user)->post(route('pengajuan.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('pengajuan.store'), $payload)->assertSessionHasNoErrors();
        $proposal = ProcurementRequest::first();
        $this->post(route('pengajuan.submit', $proposal))->assertSessionHasNoErrors();
        $this->post(route('pengajuan.submit', $proposal))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('procurement_requests', 1);
        $this->assertDatabaseCount('procurement_request_items', 1);
    }

    public function test_guests_cannot_create_or_review_requests(): void
    {
        $this->get(route('pengajuan.index'))->assertRedirect(route('login'));
        $this->post(route('pengajuan.store'), $this->payload())->assertRedirect(route('login'));
        $this->assertDatabaseCount('procurement_requests', 0);
    }

    public function test_invalid_quantity_and_price_do_not_create_request(): void
    {
        $payload = $this->payload();
        $payload['items'][0]['quantity'] = 0;
        $payload['items'][0]['estimated_unit_price'] = '-1';
        $this->actingAs(User::factory()->create())->post(route('pengajuan.store'), $payload)->assertSessionHasErrors(['items.0.quantity', 'items.0.estimated_unit_price']);
        $this->assertDatabaseCount('procurement_requests', 0);
    }

    public function test_letter_escapes_html_and_docx_uses_current_data_without_sample_signatures(): void
    {
        $proposal = ProcurementRequest::factory()->create(['purpose' => '<script>alert(1)</script>', 'subject' => 'SURAT UJI']);
        ProcurementRequestItem::factory()->for($proposal, 'procurementRequest')->create(['name' => 'Barang Surat Uji']);
        $this->actingAs($proposal->requester)->get(route('pengajuan.letter', $proposal))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);

        $response = $this->get(route('pengajuan.document', $proposal))->assertOk()->assertDownload('Surat-Pengajuan-'.$proposal->id.'.docx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $zip->open($path);
            $xml = $zip->getFromName('word/document.xml');
            $this->assertStringContainsString('Barang Surat Uji', $xml);
            $this->assertStringContainsString('SURAT UJI', $xml);
            $this->assertStringNotContainsString('Muhammad Hasrul', $xml);
            $this->assertStringNotContainsString('Fauzan', $xml);
            $this->assertStringNotContainsString('Endang TW', $xml);
            $this->assertStringNotContainsString('XIAOMI', $xml);
            $this->assertStringNotContainsString('<w:drawing', $xml);
            $this->assertStringContainsString('Belum diperiksa', $xml);
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    private function payload(): array
    {
        return ['submission_key' => (string) Str::uuid(), 'division' => 'Operasional', 'subject' => 'Pengajuan Laptop', 'request_date' => '2026-10-06', 'purpose' => 'Kebutuhan tim operasional.', 'selection_mode' => 'all', 'items' => [['name' => 'Laptop', 'category' => 'Elektronik', 'quantity' => 2, 'unit' => 'Unit', 'estimated_unit_price' => '100.75', 'specification' => 'RAM 16 GB']]];
    }

    public static function reviewerRoles(): array
    {
        $cases = [];
        foreach (User::roleLabels() as $role => $label) {
            $cases['finance '.$role] = [$role, 'finance', in_array($role, [User::ROLE_KEUANGAN, User::ROLE_SUPERADMIN], true)];
            $cases['director '.$role] = [$role, 'director', in_array($role, [User::ROLE_DIREKSI, User::ROLE_SUPERADMIN], true)];
        }

        return $cases;
    }

    #[DataProvider('reviewerRoles')]
    public function test_review_policy_enforces_role_for_each_stage(string $role, string $level, bool $allowed): void
    {
        $proposal = ProcurementRequest::factory()->submitted()->create(['status' => $level === 'finance' ? 'pending_finance' : 'pending_director']);
        $actor = User::factory()->create(['role' => $role]);

        $this->assertSame($allowed, Gate::forUser($actor)->allows($level === 'finance' ? 'reviewFinance' : 'reviewDirector', $proposal));
    }

    public function test_staff_endpoint_cannot_record_finance_decision(): void
    {
        $proposal = ProcurementRequest::factory()->submitted()->create();
        $actor = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($actor)->post(route('pengajuan.review', [$proposal, 'finance']), ['decision' => 'approved'])->assertForbidden();

        $this->assertDatabaseCount('procurement_decisions', 0);
    }
}
