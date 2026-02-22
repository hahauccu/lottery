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

    {{-- QR Code 掃描區塊 --}}
    <div
        x-data="{
            scanning: false,
            scanner: null,
            preview: null,
            loading: false,
            manualInput: '',
            cameraError: null,

            extractClaimToken(raw) {
                raw = raw.trim();
                const urlMatch = raw.match(/\/claim\/([A-Za-z0-9]{64})/);
                if (urlMatch) return urlMatch[1];
                if (/^[A-Za-z0-9]{64}$/.test(raw)) return raw;
                return null;
            },

            async startCamera() {
                this.cameraError = null;
                this.preview = null;
                this.scanning = true;

                await this.$nextTick();

                if (!this.scanner) {
                    this.scanner = new Html5Qrcode('qr-reader');
                }

                try {
                    await this.scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        (decodedText) => this.onScanSuccess(decodedText),
                        () => {}
                    );
                } catch (err) {
                    console.error('[QR Scanner]', err);
                    this.cameraError = '無法開啟相機：' + (err?.message || err);
                    this.scanning = false;
                }
            },

            async stopCamera() {
                if (this.scanner && this.scanning) {
                    try {
                        await this.scanner.stop();
                    } catch (e) {}
                }
                this.scanning = false;
            },

            async onScanSuccess(text) {
                await this.stopCamera();
                await this.lookup(text);
            },

            async submitManual() {
                if (!this.manualInput.trim()) return;
                await this.lookup(this.manualInput);
            },

            async lookup(rawInput) {
                this.loading = true;
                this.preview = null;
                this.cameraError = null;

                try {
                    const result = await $wire.previewByToken(rawInput);
                    this.preview = result;
                } catch (err) {
                    this.preview = { found: false, error: '查詢失敗，請重試' };
                }

                this.loading = false;
            },

            async confirm() {
                if (!this.preview?.token) return;
                this.loading = true;

                try {
                    await $wire.confirmClaim(this.preview.token);
                    this.preview.claimed = true;
                    this.preview.claimed_at = '剛剛';
                } catch (err) {}

                this.loading = false;
            },

            reset() {
                this.preview = null;
                this.manualInput = '';
                this.cameraError = null;
            }
        }"
        x-on:keydown.enter.prevent="submitManual()"
        class="mb-6"
    >
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-qr-code class="w-5 h-5" />
                    掃描 QR Code 領獎
                </div>
            </x-slot>

            <div class="space-y-4">
                {{-- 區塊 A：相機掃描 --}}
                <div>
                    <div class="flex gap-2 mb-3">
                        <button
                            x-show="!scanning"
                            x-on:click="startCamera()"
                            type="button"
                            class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                            style="--c-400:var(--success-400);--c-500:var(--success-500);--c-600:var(--success-600);"
                        >
                            <x-heroicon-m-camera class="w-5 h-5" />
                            開啟相機掃描
                        </button>

                        <button
                            x-show="scanning"
                            x-on:click="stopCamera()"
                            type="button"
                            class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-danger fi-color-danger fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                            style="--c-400:var(--danger-400);--c-500:var(--danger-500);--c-600:var(--danger-600);"
                        >
                            <x-heroicon-m-stop class="w-5 h-5" />
                            停止相機
                        </button>
                    </div>

                    <div
                        id="qr-reader"
                        x-show="scanning"
                        class="max-w-sm mx-auto rounded-lg overflow-hidden"
                    ></div>

                    <template x-if="cameraError">
                        <div class="p-3 rounded-lg bg-danger-50 dark:bg-danger-950 text-danger-600 dark:text-danger-400 text-sm">
                            <span x-text="cameraError"></span>
                            <span class="block mt-1">請使用下方手動輸入功能。</span>
                        </div>
                    </template>
                </div>

                {{-- 區塊 B：預覽卡 --}}
                <template x-if="preview">
                    <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-4">
                        <template x-if="preview.found">
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">姓名</span>
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="preview.name"></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">獎項</span>
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="preview.prize"></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">序號</span>
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="'#' + preview.sequence"></p>
                                    </div>
                                    <div x-show="preview.department">
                                        <span class="text-gray-500 dark:text-gray-400">部門</span>
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="preview.department"></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">中獎時間</span>
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="preview.won_at"></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">狀態</span>
                                        <p>
                                            <span
                                                x-show="preview.claimed"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400"
                                            >
                                                已領獎
                                                <span x-show="preview.claimed_at" x-text="'(' + preview.claimed_at + ')'"></span>
                                            </span>
                                            <span
                                                x-show="!preview.claimed"
                                                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400"
                                            >
                                                待領獎
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-white/5">
                                    <button
                                        x-on:click="confirm()"
                                        x-bind:disabled="preview.claimed || loading"
                                        type="button"
                                        class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        style="--c-400:var(--success-400);--c-500:var(--success-500);--c-600:var(--success-600);"
                                    >
                                        <template x-if="loading">
                                            <x-filament::loading-indicator class="w-5 h-5" />
                                        </template>
                                        <template x-if="!loading">
                                            <x-heroicon-m-check-circle class="w-5 h-5" />
                                        </template>
                                        <span x-text="preview.claimed ? '已領獎' : '確認領獎'"></span>
                                    </button>

                                    <button
                                        x-on:click="reset()"
                                        type="button"
                                        class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                                    >
                                        取消
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="!preview.found">
                            <div class="flex items-center gap-2 text-danger-600 dark:text-danger-400">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 flex-shrink-0" />
                                <span x-text="preview.error"></span>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Loading --}}
                <div x-show="loading && !preview" class="flex items-center justify-center py-4">
                    <x-filament::loading-indicator class="w-6 h-6 text-primary-500" />
                    <span class="ml-2 text-sm text-gray-500">查詢中...</span>
                </div>

                {{-- 區塊 C：手動輸入 --}}
                <div class="flex gap-2">
                    <input
                        x-model="manualInput"
                        type="text"
                        placeholder="貼上領獎代碼或完整 URL"
                        class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white dark:bg-white/5 rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20 px-3"
                    />
                    <button
                        x-on:click="submitManual()"
                        x-bind:disabled="loading || !manualInput.trim()"
                        type="button"
                        class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                        style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                    >
                        <x-heroicon-m-magnifying-glass class="w-5 h-5" />
                        查詢
                    </button>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{ $this->table }}

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    @endpush
</x-filament-panels::page>
