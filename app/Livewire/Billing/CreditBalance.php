<?php

namespace App\Livewire\Billing;

use App\Services\Billing\CreditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CreditBalance extends Component
{
    public int $balance = 0;

    public int $overdraft = 0;

    protected CreditService $creditService;

    public function boot(CreditService $creditService): void
    {
        $this->creditService = $creditService;
    }

    public function mount(): void
    {
        $this->refreshBalance();
    }

    #[On('credits-updated')]
    public function refreshBalance(): void
    {
        $organization = Auth::user()->organization;
        $this->balance = $organization->credit_balance;
        $this->overdraft = $organization->overdraft_balance;
    }

    public function render(): View
    {
        return view('livewire.billing.credit-balance');
    }
}
