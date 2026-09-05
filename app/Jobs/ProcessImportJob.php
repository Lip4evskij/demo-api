<?php

namespace App\Jobs;

use App\DTO\ImportPayloadDTO;
use App\Models\Import;
use App\Repositories\ImportRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Import $import) {}

    public function handle(ImportRepository $repository): void
    {
        $import = $this->import->fresh();

        if ($import === null || $import->status === Import::STATUS_COMPLETED) {
            return;
        }

        $dto = ImportPayloadDTO::fromArray($import->raw_payload);

        $import->forceFill(['status' => Import::STATUS_PROCESSING])->save();

        try {
            $processed = DB::transaction(
                fn () => $repository->upsertOffers($import->supplier, $dto)
            );

            $import->forceFill([
                'status' => Import::STATUS_COMPLETED,
                'total_offers' => count($dto->offers),
                'processed_offers' => $processed,
                'error' => null,
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $import->forceFill([
                'status' => Import::STATUS_FAILED,
                'error' => $exception->getMessage(),
                'completed_at' => now(),
            ])->save();
        }
    }
}
