<?php
namespace App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource;
use App\Services\ExpenseService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        return app(ExpenseService::class)->record($data);
    }
}
