<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll;

use App\Domain\Payroll\Models\PayrollBatch;
use App\Filament\Resources\Payroll\Pages\ListPayrollBatches;
use App\Filament\Resources\Payroll\Pages\ViewPayrollBatch;
use App\Filament\Resources\Payroll\Tables\PayrollBatchesTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PayrollBatchResource extends Resource
{
    protected static ?string $model = PayrollBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Batch Penggajian';

    protected static ?string $pluralModelLabel = 'Batch Penggajian';

    protected static ?string $slug = 'payroll-batches';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return PayrollBatchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollBatches::route('/'),
            'view' => ViewPayrollBatch::route('/{record}'),
        ];
    }
}
