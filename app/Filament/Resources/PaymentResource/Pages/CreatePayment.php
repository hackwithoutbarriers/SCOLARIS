<?php
namespace App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource;
use App\Services\Payments\PaymentService;
use Filament\Resources\Pages\CreateRecord;
class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(PaymentService::class)->recordManual($data);
    }
}
