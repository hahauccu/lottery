<div class="space-y-3">
    <div class="text-sm text-gray-600">
        可抽人數：<span class="font-semibold text-gray-900">{{ $total }}</span>
    </div>

    @if ($total === 0)
        <div class="rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-500">
            尚無符合條件的員工
        </div>
    @else
        <div class="max-h-72 overflow-auto rounded-md border border-gray-200">
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach ($items as $employee)
                    <li class="flex items-center justify-between px-4 py-2">
                        <span class="font-medium text-gray-900">{{ $employee->name }}</span>
                        <span class="text-gray-500">
                            {{ $employee->email }}
                            @if($employee->phone)
                                · {{ $employee->phone }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="flex items-center justify-between text-sm text-gray-600">
            <button type="button" class="rounded border px-3 py-1 disabled:opacity-50" wire:click="previousPage" @if($page <= 1) disabled @endif>
                上一頁
            </button>
            <span>第 {{ $page }} 頁</span>
            <button type="button" class="rounded border px-3 py-1 disabled:opacity-50" wire:click="nextPage" @if(! $hasMore) disabled @endif>
                下一頁
            </button>
        </div>
    @endif
</div>
