<?php

namespace App\Http\Controllers\Api;

use App\DTO\ImportPayloadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportRequest;
use App\Http\Resources\ImportAcceptedResource;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Repositories\ImportRepository;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function __construct(private readonly ImportRepository $imports) {}

    public function store(ImportRequest $request): JsonResponse
    {
        $dto = ImportPayloadDTO::fromArray($request->validated());

        $existing = $this->imports->findExisting($dto->supplierSlug, $dto->externalImportId);

        if ($existing !== null) {
            return (new ImportAcceptedResource($existing))->response()->setStatusCode(202);
        }

        $import = $this->imports->createFromPayload($dto);

        ProcessImportJob::dispatch($import);

        return (new ImportAcceptedResource($import))->response()->setStatusCode(202);
    }

    public function show(Import $import): ImportResource
    {
        return new ImportResource($import->loadMissing('supplier'));
    }
}
