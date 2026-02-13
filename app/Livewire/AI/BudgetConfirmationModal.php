<?php

namespace App\Livewire\AI;

use Livewire\Attributes\On;
use Livewire\Component;

class BudgetConfirmationModal extends Component
{
    public bool $show = false;

    public ?float $currentUsage = null;

    public ?float $monthlyLimit = null;

    public ?float $usagePercentage = null;

    public ?string $message = null;

    public string $actionId = '';

    #[On('show-budget-confirmation')]
    public function showConfirmation(
        string $actionId,
        float $currentUsage,
        float $monthlyLimit,
        float $usagePercentage,
        string $message
    ): void {
        $this->actionId = $actionId;
        $this->currentUsage = $currentUsage;
        $this->monthlyLimit = $monthlyLimit;
        $this->usagePercentage = $usagePercentage;
        $this->message = $message;
        $this->show = true;
    }

    public function confirm(): void
    {
        $this->dispatch('budget-override-confirmed', actionId: $this->actionId);
        $this->close();
    }

    public function cancel(): void
    {
        $this->dispatch('budget-override-cancelled', actionId: $this->actionId);
        $this->close();
    }

    public function close(): void
    {
        $this->show = false;
        $this->reset(['currentUsage', 'monthlyLimit', 'usagePercentage', 'message', 'actionId']);
    }

    public function getRemainingBudget(): float
    {
        if ($this->monthlyLimit === null || $this->currentUsage === null) {
            return 0;
        }

        return max(0, $this->monthlyLimit - $this->currentUsage);
    }

    public function getOverageAmount(): float
    {
        if ($this->monthlyLimit === null || $this->currentUsage === null) {
            return 0;
        }

        return max(0, $this->currentUsage - $this->monthlyLimit);
    }

    public function render()
    {
        return view('livewire.ai.budget-confirmation-modal');
    }
}
