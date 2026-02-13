<?php

namespace App\Livewire\Billing;

use App\Services\Billing\CreditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class BillingHistory extends Component
{
    use WithPagination;

    public string $filter = 'all';

    protected CreditService $creditService;

    public function boot(CreditService $creditService): void
    {
        $this->creditService = $creditService;
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $organization = Auth::user()->organization;

        $transactions = $organization->creditTransactions()
            ->with('user:id,name')
            ->when($this->filter !== 'all', function ($query) {
                $query->where('type', $this->filter);
            })
            ->latest()
            ->paginate(20);

        $usageStats = $this->creditService->getUsageStats($organization);

        return view('livewire.billing.billing-history', [
            'transactions' => $transactions,
            'usageStats' => $usageStats,
            'organization' => $organization,
        ]);
    }
}
