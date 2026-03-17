<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                聯絡資訊
            </x-slot>

            <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                <x-heroicon-o-envelope class="w-5 h-5" />
                <span>Email：<a href="mailto:dindin1688888888@gmail.com" class="text-primary-500 hover:underline">dindin1688888888@gmail.com</a></span>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                聯絡表單
            </x-slot>

            <form wire:submit="submit" class="space-y-4">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">送出</span>
                        <span wire:loading wire:target="submit">送出中...</span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
