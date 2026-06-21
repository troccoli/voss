<div class="min-h-screen bg-linear-to-br from-amber-50 via-white to-sky-50 px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
        <flux:card class="border border-slate-200/80 bg-white/90 p-6 shadow-sm backdrop-blur sm:p-8">
            <div class="flex flex-col gap-4">
                <div class="space-y-3">
                    <flux:badge color="sky" inset="top bottom">Competition</flux:badge>
                    <div class="space-y-2">
                        <flux:heading size="xl">Set the competition details</flux:heading>
                        <flux:text class="max-w-2xl">
                            Keep the current competition name in the app instead of relying on config defaults.
                        </flux:text>
                    </div>
                </div>

                @if ($saved)
                    <flux:callout color="green" icon="check-circle">
                        Competition details saved.
                    </flux:callout>
                @endif
            </div>
        </flux:card>

        <flux:card class="border border-slate-200/80 bg-white/90 p-6 shadow-sm sm:p-8">
            <form wire:submit="save" class="space-y-6">
                <flux:field>
                    <flux:label>Competition name</flux:label>
                    <flux:input wire:model.live="name" placeholder="e.g. Nations League Finals" />
                    <flux:text>
                        This name is used anywhere the app renders the current competition label.
                    </flux:text>
                    <flux:error name="name" />
                </flux:field>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                        Save competition
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</div>
