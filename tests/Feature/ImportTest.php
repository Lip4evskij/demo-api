<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Supplier;
use App\Repositories\ImportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-2026-09-01-001',
            'sent_at' => '2026-09-01T10:00:00Z',
            'offers' => [
                [
                    'external_id' => 'offer-a-10001',
                    'property' => [
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia',
                        'city' => 'Barcelona',
                    ],
                    'check_in' => '2026-10-10',
                    'check_out' => '2026-10-15',
                    'max_guests' => 4,
                    'price' => 72500,
                    'currency' => 'EUR',
                    'available_units' => 2,
                    'expires_at' => '2026-09-10T23:59:59Z',
                ],
            ],
        ], $overrides);
    }

    public function test_import_is_accepted_and_dispatches_job(): void
    {
        Supplier::factory()->create(['slug' => 'supplier-a']);
        Queue::fake();

        $response = $this->postJson('/api/imports', $this->validPayload());

        $response->assertStatus(202);
        $response->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('imports', [
            'external_import_id' => 'import-2026-09-01-001',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessImportJob::class);
    }

    public function test_job_processes_import_to_completed(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);

        $this->postJson('/api/imports', $this->validPayload());

        $import = Import::query()->where('supplier_id', $supplier->id)->firstOrFail();

        (new ProcessImportJob($import->id))->handle(app(ImportRepository::class));

        $import->refresh();

        $this->assertSame('completed', $import->status);
        $this->assertSame(1, $import->processed_offers);
        $this->assertDatabaseHas('properties', ['code' => 'BCN-0001']);
        $this->assertDatabaseHas('offers', [
            'external_id' => 'offer-a-10001',
            'available_units' => 2,
        ]);
    }

    public function test_duplicate_import_is_idempotent(): void
    {
        Supplier::factory()->create(['slug' => 'supplier-a']);
        Queue::fake();

        $this->postJson('/api/imports', $this->validPayload())->assertStatus(202);
        $this->postJson('/api/imports', $this->validPayload())->assertStatus(422);

        $this->assertSame(1, Import::query()->count());
        Queue::assertPushed(ProcessImportJob::class, 1);
    }

    public function test_repeated_offer_upserts_instead_of_duplicating(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $repository = app(ImportRepository::class);

        $this->postJson('/api/imports', $this->validPayload());
        $firstImport = Import::query()->where('supplier_id', $supplier->id)->firstOrFail();
        (new ProcessImportJob($firstImport->id))->handle($repository);

        $this->postJson('/api/imports', $this->validPayload([
            'external_import_id' => 'import-2026-09-01-002',
            'offers' => [
                [
                    'external_id' => 'offer-a-10001',
                    'property' => [
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia',
                        'city' => 'Barcelona',
                    ],
                    'check_in' => '2026-10-10',
                    'check_out' => '2026-10-15',
                    'max_guests' => 4,
                    'price' => 65000,
                    'currency' => 'EUR',
                    'available_units' => 1,
                    'expires_at' => '2026-09-12T23:59:59Z',
                ],
            ],
        ]));
        $secondImport = Import::query()->where('external_import_id', 'import-2026-09-01-002')->firstOrFail();
        (new ProcessImportJob($secondImport->id))->handle($repository);

        $this->assertSame(1, Offer::query()->where('external_id', 'offer-a-10001')->count());
        $this->assertDatabaseHas('offers', [
            'external_id' => 'offer-a-10001',
            'price' => 65000,
            'available_units' => 1,
        ]);
    }

    public function test_invalid_payload_returns_422(): void
    {
        $response = $this->postJson('/api/imports', [
            'supplier' => 'supplier-a',
        ]);

        $response->assertStatus(422);
    }

    public function test_unknown_supplier_returns_422(): void
    {
        $response = $this->postJson('/api/imports', $this->validPayload(['supplier' => 'unknown-supplier']));

        $response->assertStatus(422);
    }

    public function test_show_returns_import_status(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/imports/{$import->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $import->id);
        $response->assertJsonPath('data.supplier.slug', 'supplier-a');
        $response->assertJsonPath('data.status', 'completed');
    }
}
