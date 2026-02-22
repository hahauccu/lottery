<x-filament-panels::page>
    @php
        $helpListClasses = 'not-prose list-disc list-inside space-y-1 text-sm leading-7 text-gray-700 dark:text-gray-200';
        $helpItemClasses = 'text-gray-700 dark:text-gray-200 marker:text-gray-400 dark:marker:text-gray-500';
        $helpSubListClasses = 'not-prose list-disc list-inside ml-4 mt-1 space-y-0.5 text-sm leading-7 text-gray-700 dark:text-gray-200';
    @endphp

    <div x-data="{ tab: 'lottery' }">
        {{-- Tab 按鈕 --}}
        <div class="flex gap-2 mb-6">
            <button
                @click="tab = 'lottery'"
                :class="tab === 'lottery'
                    ? 'bg-primary-600 text-white shadow-md ring-1 ring-primary-400/40'
                    : 'bg-white text-gray-700 hover:bg-gray-50 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:ring-white/5'"
                class="px-5 py-2.5 rounded-lg font-medium text-sm transition-all duration-200"
            >
                抽獎活動
            </button>
            <button
                @click="tab = 'employee'"
                :class="tab === 'employee'
                    ? 'bg-primary-600 text-white shadow-md ring-1 ring-primary-400/40'
                    : 'bg-white text-gray-700 hover:bg-gray-50 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:ring-white/5'"
                class="px-5 py-2.5 rounded-lg font-medium text-sm transition-all duration-200"
            >
                員工匯入
            </button>
        </div>

        {{-- 抽獎活動 --}}
        <div x-show="tab === 'lottery'" x-cloak>
            <div class="space-y-4">
                <x-filament::section>
                    <x-slot name="heading">建立與管理活動</x-slot>
                    <ul class="{{ $helpListClasses }}">
                        <li class="{{ $helpItemClasses }}">使用者可新增一個抽獎活動來舉辦抽獎。</li>
                        <li class="{{ $helpItemClasses }}">活動建立後，可透過編輯頁管理整場抽獎設定。</li>
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">開放抽獎</x-slot>
                    <ul class="{{ $helpListClasses }}">
                        <li class="{{ $helpItemClasses }}"><strong>開放抽獎 = 開</strong>：前台可執行抽獎。</li>
                        <li class="{{ $helpItemClasses }}"><strong>開放抽獎 = 關</strong>：前台不可抽獎，可避免誤觸。</li>
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">啟用彈幕</x-slot>
                    <ul class="{{ $helpListClasses }}">
                        <li class="{{ $helpItemClasses }}">開啟後，前台中獎清單頁會出現彈幕發送 UI。</li>
                        <li class="{{ $helpItemClasses }}">發送者需輸入員工 Email；必須是該活動組織內的員工才可發送。</li>
                        <li class="{{ $helpItemClasses }}">彈幕顯示名稱為員工姓名。</li>
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">獎項設定與可抽對象</x-slot>
                    <ul class="{{ $helpListClasses }}">
                        <li class="{{ $helpItemClasses }}">每場活動可建立多個獎項。</li>
                        <li class="{{ $helpItemClasses }}">每個獎項可設定包含／排除：
                            <ul class="{{ $helpSubListClasses }}">
                                <li class="{{ $helpItemClasses }}">包含員工、包含群組</li>
                                <li class="{{ $helpItemClasses }}">排除員工、排除群組</li>
                            </ul>
                        </li>
                        <li class="{{ $helpItemClasses }}">規則：包含為聯集，排除優先；被排除者不可參與該獎項抽獎。</li>
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">切換顯示</x-slot>
                    <ul class="{{ $helpListClasses }}">
                        <li class="{{ $helpItemClasses }}">可透過「設為目前獎項」切換前台抽獎獎項。</li>
                        <li class="{{ $helpItemClasses }}">也可切換至預覽畫面。</li>
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">發送中獎通知與領獎</x-slot>
                    <ul class="{{ $helpListClasses }}">
                        <li class="{{ $helpItemClasses }}">可發送中獎通知 Email 給員工。</li>
                        <li class="{{ $helpItemClasses }}">信件包含領獎 QRCode。</li>
                        <li class="{{ $helpItemClasses }}">已支援手機在後台開啟相機掃描 QRCode 領獎。</li>
                        <li class="{{ $helpItemClasses }}">領獎流程為「先預覽中獎資訊，再確認領獎」，避免誤領。</li>
                        <li class="{{ $helpItemClasses }}">一般使用者掃碼僅能查看資訊，不能直接完成後台領獎寫入。</li>
                    </ul>
                </x-filament::section>

                <div class="mt-4">
                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\Resources\LotteryEventResource::getUrl('index')"
                    >
                        前往抽獎活動
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- 員工匯入 --}}
        <div x-show="tab === 'employee'" x-cloak>
            <div class="space-y-4">
                <x-filament::section>
                    <x-slot name="heading">匯入方式</x-slot>
                    <ul class="{{ $helpListClasses }}">
                        <li class="{{ $helpItemClasses }}">可在後台匯入員工 CSV。</li>
                        <li class="{{ $helpItemClasses }}">請先下載範例 CSV，並依範例欄位填寫。</li>
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">匯入規則</x-slot>
                    <ul class="{{ $helpListClasses }}">
                        <li class="{{ $helpItemClasses }}">每位員工以 Email 識別（同公司內唯一）。</li>
                        <li class="{{ $helpItemClasses }}">同一檔案若有重複 Email，只記錄第一筆。</li>
                        <li class="{{ $helpItemClasses }}">若資料庫已存在該 Email，系統會更新員工資料。</li>
                        <li class="{{ $helpItemClasses }}">若匯入資料有填群組，系統會自動建立不存在的群組並指派。</li>
                        <li class="{{ $helpItemClasses }}">若 CSV 有群組欄位但該列留空，會清空該員工既有群組。</li>
                    </ul>
                </x-filament::section>

                <div class="mt-4">
                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\Resources\EmployeeResource::getUrl('index')"
                    >
                        前往員工管理
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
