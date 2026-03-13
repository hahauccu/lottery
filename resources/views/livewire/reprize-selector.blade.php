<div class="space-y-4">
    {{-- 搜尋框 --}}
    <div>
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="搜尋姓名、email、部門..."
            class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500 sm:text-sm sm:leading-6 bg-white dark:bg-white/5 rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20 px-3"
        />
    </div>

    {{-- 中獎者清單 --}}
    @if($winners->isEmpty())
        <div class="rounded-md border border-dashed border-gray-300 dark:border-gray-700 p-4 text-sm text-gray-500 dark:text-gray-400 text-center">
            沒有找到中獎者
        </div>
    @else
        <div class="max-h-80 overflow-auto rounded-lg border border-gray-200 dark:border-white/10">
            <ul class="divide-y divide-gray-100 dark:divide-white/5 text-sm">
                @foreach($winners as $winner)
                    <li class="flex items-center gap-3 px-4 py-3 {{ $winner->isReleased() ? 'opacity-50 bg-gray-50 dark:bg-white/5' : '' }}">
                        {{-- Checkbox --}}
                        <div class="flex-shrink-0">
                            @if($winner->isReleasable())
                                <input
                                    type="checkbox"
                                    wire:click="toggleSelect({{ $winner->id }})"
                                    @checked(in_array($winner->id, $selected))
                                    class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600 dark:border-white/20 dark:bg-white/5"
                                />
                            @else
                                <input type="checkbox" disabled class="fi-checkbox-input rounded border-gray-300 opacity-30 cursor-not-allowed dark:border-white/20" />
                            @endif
                        </div>

                        {{-- 資訊 --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900 dark:text-white">#{{ $winner->sequence }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white truncate">{{ $winner->employee->name }}</span>
                                @if($winner->employee->department)
                                    <span class="text-gray-500 dark:text-gray-400 truncate">{{ $winner->employee->department }}</span>
                                @endif
                            </div>
                            @if($winner->employee->email)
                                <div class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $winner->employee->email }}</div>
                            @endif
                        </div>

                        {{-- 狀態 Badge --}}
                        <div class="flex-shrink-0 flex gap-1">
                            @if($winner->isReleased())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                    已釋出
                                </span>
                            @elseif($winner->isClaimed())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                    已領獎
                                </span>
                            @elseif($winner->isNotified())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                                    已通知
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-yellow-50 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                                    已中獎
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 確認區域 --}}
    @if(!$confirming && count($selected) > 0)
        <div class="flex items-center justify-between pt-2">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                已選擇 <strong class="text-gray-900 dark:text-white">{{ count($selected) }}</strong> 人
            </span>
            <button
                wire:click="startConfirm"
                type="button"
                class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-warning fi-color-warning fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                style="--c-400:var(--warning-400);--c-500:var(--warning-500);--c-600:var(--warning-600);"
            >
                取消 {{ count($selected) }} 人得獎並釋出重抽
            </button>
        </div>
    @endif

    {{-- 最終確認 --}}
    @if($confirming)
        <div class="rounded-xl border-2 border-warning-500 dark:border-warning-400 bg-warning-50 dark:bg-warning-500/10 p-4 space-y-3">
            <div class="flex items-center gap-2 text-warning-700 dark:text-warning-400 font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                確認釋出以下中獎者？
            </div>

            <ul class="space-y-1 text-sm">
                @foreach($selectedWinners as $sw)
                    <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span>#{{ $sw->sequence }}</span>
                        <span class="font-medium">{{ $sw->employee->name }}</span>
                        @if($sw->employee->email)
                            <span class="text-gray-500 dark:text-gray-400">({{ $sw->employee->email }})</span>
                        @endif
                        @if($sw->isNotified())
                            <span class="text-xs text-blue-600 dark:text-blue-400">⚠ 已通知</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="flex gap-2 pt-2">
                <button
                    wire:click="executeRelease"
                    wire:loading.attr="disabled"
                    type="button"
                    class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-danger fi-color-danger fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                    style="--c-400:var(--danger-400);--c-500:var(--danger-500);--c-600:var(--danger-600);"
                >
                    <span wire:loading.remove wire:target="executeRelease">確認釋出</span>
                    <span wire:loading wire:target="executeRelease">處理中...</span>
                </button>
                <button
                    wire:click="cancelConfirm"
                    type="button"
                    class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                >
                    返回
                </button>
            </div>
        </div>
    @endif
</div>
