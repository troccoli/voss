<div>
    @unless ($hasSubmittedToss)
        <div class="absolute bottom-6 right-6 z-20">
            <flux:modal.trigger name="submit-toss-result">
                <flux:button variant="primary" icon="clipboard-document-check">
                    Submit Toss Result
                </flux:button>
            </flux:modal.trigger>

            <flux:modal name="submit-toss-result" class="min-w-[30rem]">
                <form wire:submit="submit" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Submit Toss Result</flux:heading>
                        <flux:text class="mt-2">
                            Select which side is Team A and which team serves first.
                        </flux:text>
                    </div>

                    <flux:radio.group
                        wire:model="teamA"
                        label="Team A"
                        variant="segmented"
                        :invalid="$errors->has('teamA')"
                    >
                        <flux:radio value="home" label="Home Team" />
                        <flux:radio value="away" label="Away Team" />
                    </flux:radio.group>
                    @error('teamA')
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror

                    <flux:radio.group
                        wire:model="serving"
                        label="Serving Team"
                        variant="segmented"
                        :invalid="$errors->has('serving')"
                    >
                        <flux:radio value="team_a" label="Team A" />
                        <flux:radio value="team_b" label="Team B" />
                    </flux:radio.group>
                    @error('serving')
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror

                    @error('submit')
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror

                    <div class="flex items-center gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Save Toss Result</flux:button>
                    </div>
                </form>
            </flux:modal>
        </div>
    @endunless
</div>
