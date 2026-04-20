<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = Address::query()
            ->where('user_id', request()->user()->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return $this->successResponse(AddressResource::collection($addresses));
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $request->user()->addresses()->create($request->validated());

        return $this->successResponse(
            new AddressResource($address),
            'Address saved successfully.',
            201,
        );
    }

    public function update(UpdateAddressRequest $request, int $id): JsonResponse
    {
        $address = Address::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $address->fill($request->validated());
        $address->save();

        return $this->successResponse(
            new AddressResource($address),
            'Address updated successfully.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $address = Address::query()
            ->where('user_id', request()->user()->id)
            ->findOrFail($id);

        $address->delete();

        return $this->successResponse([
            'deleted' => true,
        ], 'Address deleted successfully.');
    }

    public function setDefault(int $id): JsonResponse
    {
        $address = Address::query()
            ->where('user_id', request()->user()->id)
            ->findOrFail($id);

        $address->is_default = true;
        $address->save();

        return $this->successResponse(
            new AddressResource($address),
            'Default address updated successfully.',
        );
    }
}
