<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Request\Request;
use App\Services\Request\RequestOrchestrator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    public function mount(): void
    {
        parent::mount();

        $requestId = request()->query('request');
        if ($requestId) {
            $this->fillFromRequest($requestId);
        }
    }

    public function updated($name, $value): void
    {
        if ($name === 'data.request_id') {
            if ($value) {
                $this->fillFromRequest($value);
            } else {
                $this->data['items'] = [];
                $this->data['notes'] = '';
            }
        }
    }

    protected function fillFromRequest(string $requestId): void
    {
        $requestModel = Request::with('items')->find($requestId);
        if (! $requestModel) {
            return;
        }

        $this->data['request_id'] = $requestId;
        $this->data['notes'] = $requestModel->notes ?? '';
        $this->data['items'] = $requestModel->items->map(fn ($item) => [
            'name' => $item->name,
            'description' => null,
            'unit_price' => $item->unit_price,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'subtotal' => $item->subtotal,
        ])->toArray();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['request_id'])) {
            $requestModel = Request::find($data['request_id']);
            if ($requestModel) {
                app(RequestOrchestrator::class)
                    ->transitionRequestToQuotationRequired($requestModel, Auth::id());
            }
        }

        return $data;
    }
}
