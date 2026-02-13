<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ApiTokens extends Component
{
    public string $tokenName = '';

    public ?string $newToken = null;

    public bool $showCreateModal = false;

    public bool $showDeleteModal = false;

    public ?int $tokenToDelete = null;

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
        ]);

        $token = Auth::user()->createToken($this->tokenName);

        $this->newToken = $token->plainTextToken;
        $this->tokenName = '';
        $this->showCreateModal = false;
    }

    public function confirmDelete(int $tokenId): void
    {
        $this->tokenToDelete = $tokenId;
        $this->showDeleteModal = true;
    }

    public function deleteToken(): void
    {
        if ($this->tokenToDelete) {
            Auth::user()->tokens()->where('id', $this->tokenToDelete)->delete();
        }

        $this->tokenToDelete = null;
        $this->showDeleteModal = false;
    }

    public function revokeAllTokens(): void
    {
        Auth::user()->tokens()->delete();
    }

    public function dismissNewToken(): void
    {
        $this->newToken = null;
    }

    public function render(): View
    {
        $tokens = Auth::user()->tokens()->latest()->get();

        return view('livewire.settings.api-tokens', [
            'tokens' => $tokens,
        ]);
    }
}
