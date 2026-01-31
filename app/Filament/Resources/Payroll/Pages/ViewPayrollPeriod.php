<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payroll\Pages;

use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Services\FinalizePayrollService;
use App\Domain\Payroll\Services\PreparePayrollService;
use App\Domain\Payroll\Services\ReviewPayrollService;
use App\Filament\Resources\Payroll\PayrollPeriodResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewPayrollPeriod extends ViewRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getPrepareAction(),
            $this->getSubmitForReviewAction(),
            $this->getFinalizeAction(),
        ];
    }

    private function getPrepareAction(): Action
    {
        return Action::make('prepare')
            ->label('Prepare Payroll')
            ->icon('heroicon-o-calculator')
            ->color('gray')
            ->visible(fn(): bool => $this->record->state === PayrollState::DRAFT)
            ->action(function () {
                try {
                    app(PreparePayrollService::class)->execute($this->record, auth()->user());

                    Notification::make()
                        ->title('Payroll prepared successfully')
                        ->success()
                        ->send();

                    $this->refreshFormData(['state']);
                } catch (InvalidPayrollStateException $e) {
                    Notification::make()
                        ->title('Cannot prepare payroll')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } catch (UnauthorizedPayrollActionException $e) {
                    Notification::make()
                        ->title('Unauthorized')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function getSubmitForReviewAction(): Action
    {
        return Action::make('submitForReview')
            ->label('Submit for Review')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->visible(fn(): bool => $this->record->state === PayrollState::DRAFT)
            ->requiresConfirmation()
            ->modalHeading('Submit for Review')
            ->modalDescription('This will move the payroll to REVIEW state. The owner will need to review and finalize it.')
            ->modalSubmitActionLabel('Yes, Submit')
            ->modalCancelActionLabel('Cancel')
            ->modalWidth('md')
            ->closeModalByClickingAway(false)
            ->action(function () {
                try {
                    app(ReviewPayrollService::class)->execute($this->record, auth()->user());

                    Notification::make()
                        ->title('Submitted for review')
                        ->success()
                        ->send();

                    $this->refreshFormData(['state', 'reviewed_at']);
                } catch (InvalidPayrollStateException $e) {
                    Notification::make()
                        ->title('Cannot submit for review')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } catch (UnauthorizedPayrollActionException $e) {
                    Notification::make()
                        ->title('Unauthorized')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } catch (\DomainException $e) {
                    Notification::make()
                        ->title('Cannot submit for review')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function getFinalizeAction(): Action
    {
        return Action::make('finalize')
            ->label('Finalize Payroll')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn(): bool => $this->record->state === PayrollState::REVIEW)
            ->requiresConfirmation()
            ->modalHeading('Finalize Payroll')
            ->modalDescription('This action is IRREVERSIBLE. The payroll will be frozen and cannot be modified. Are you sure you want to finalize?')
            ->modalSubmitActionLabel('Yes, Finalize')
            ->modalCancelActionLabel('Cancel')
            ->modalWidth('md')
            ->closeModalByClickingAway(false)
            ->action(function () {
                try {
                    app(FinalizePayrollService::class)->execute($this->record, auth()->user());

                    Notification::make()
                        ->title('Payroll finalized')
                        ->body('The payroll batch has been created and frozen.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['state', 'finalized_at']);
                } catch (InvalidPayrollStateException $e) {
                    Notification::make()
                        ->title('Cannot finalize payroll')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } catch (UnauthorizedPayrollActionException $e) {
                    Notification::make()
                        ->title('Unauthorized')
                        ->body('Only the owner can finalize payroll.')
                        ->danger()
                        ->send();
                }
            });
    }
}
