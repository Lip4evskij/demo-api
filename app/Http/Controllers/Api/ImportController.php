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
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function __construct(private readonly ImportRepository $imports) {}

    public function store(ImportRequest $request): JsonResponse
    {
        $import = $this
            ->imports
            ->createFromPayload(dto: ImportPayloadDTO::fromArray($request->validated()));

        ProcessImportJob::dispatch($import->id);

        return ImportAcceptedResource::make($import)
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function show(Import $import): ImportResource
    {
        return ImportResource::make($import->loadMissing('supplier'));
    }
}
