<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Competition as CompetitionModel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Competition')]
class Competition extends Component
{
    public string $name = '';

    public bool $saved = false;

    public function mount(): void
    {
        $competition = CompetitionModel::current();

        $this->name = $competition !== null
            ? $competition->name
            : config('competition.name');
    }

    public function save(): void
    {
        $this->name = trim($this->name);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $competition = CompetitionModel::ensureSingleton();
        $competition->forceFill([
            'name' => trim((string) $validated['name']),
        ])->save();

        $this->name = $competition->name;
        $this->saved = true;
    }

    public function updatedName(): void
    {
        $this->saved = false;
    }

    public function render(): View
    {
        return view('livewire.competition');
    }
}
