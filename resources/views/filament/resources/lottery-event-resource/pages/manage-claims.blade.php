<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $this->getWinnersCount() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    總中獎人數
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-info-600 dark:text-info-400">
                    {{ $this->getNotifiedCount() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    已發送通知
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-success-600 dark:text-success-400">
                    {{ $this->getClaimedCount() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    已領獎
                </div>
            </div>
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
