<div class="text-center py-4">
    <div class="mb-4">
        <img src="{{ $winner->generateQrCodeBase64() }}" alt="QR Code" class="mx-auto" style="max-width: 200px;">
    </div>

    <div class="space-y-2 text-sm">
        <div class="flex justify-between px-4">
            <span class="text-gray-500 dark:text-gray-400">獎項</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $winner->prize->name }}</span>
        </div>
        <div class="flex justify-between px-4">
            <span class="text-gray-500 dark:text-gray-400">姓名</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $winner->employee->name }}</span>
        </div>
        <div class="flex justify-between px-4">
            <span class="text-gray-500 dark:text-gray-400">序號</span>
            <span class="font-medium text-gray-900 dark:text-white">#{{ $winner->sequence }}</span>
        </div>
        @if($winner->employee->department)
        <div class="flex justify-between px-4">
            <span class="text-gray-500 dark:text-gray-400">部門</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $winner->employee->department }}</span>
        </div>
        @endif
        <div class="flex justify-between px-4">
            <span class="text-gray-500 dark:text-gray-400">中獎時間</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $winner->won_at->format('Y/m/d H:i') }}</span>
        </div>
    </div>

    @if($winner->isClaimed())
    <div class="mt-4 p-2 bg-success-50 dark:bg-success-900/20 rounded-lg">
        <span class="text-success-600 dark:text-success-400 text-sm font-medium">
            已於 {{ $winner->claimed_at->format('Y/m/d H:i') }} 領獎
        </span>
    </div>
    @else
    <div class="mt-4 p-2 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
        <span class="text-warning-600 dark:text-warning-400 text-sm font-medium">
            待領獎
        </span>
    </div>
    @endif

    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-400 break-all">
            {{ $winner->getClaimUrl() }}
        </p>
    </div>
</div>
