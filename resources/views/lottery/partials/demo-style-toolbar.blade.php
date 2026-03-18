{{-- Demo 風格頁工具列 --}}
<div x-data="demoStyleToolbar" class="fixed inset-0 z-50 pointer-events-none" style="font-family: system-ui, -apple-system, sans-serif;">

    {{-- ===== A) 選擇 UI overlay ===== --}}
    <div x-show="setupVisible" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute inset-0 z-60 flex items-center justify-center bg-black/90 backdrop-blur-sm pointer-events-auto"
        id="demo-setup">

        <div class="w-full max-w-2xl px-5">
            {{-- 標題 --}}
            <div class="mb-8 text-center">
                <a href="{{ url('/demo/lottery') }}"
                    class="inline-block mb-4 text-xs text-white/40 hover:text-amber-400 transition">
                    ← 返回選擇風格
                </a>
                <h2 class="text-3xl font-bold text-white tracking-wide">{{ $demoStyleLabel }}</h2>
                <p class="mt-2 text-sm text-white/50">選擇一種方式開始體驗抽獎</p>
            </div>

            {{-- 兩張卡片 --}}
            <div x-show="!showCustomForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- 預設抽獎 --}}
                <button @@click="startDefault()"
                    :disabled="loading"
                    class="group relative flex flex-col items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-8 text-center transition hover:border-amber-500/40 hover:bg-amber-500/5 disabled:opacity-50">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-500/10 text-2xl group-hover:bg-amber-500/20 transition">
                        🎲
                    </div>
                    <div class="text-lg font-semibold text-white">預設抽獎</div>
                    <div class="text-xs text-white/45 leading-relaxed">
                        使用 30 位預設名單<br>每次抽出 5 人
                    </div>
                    <div class="mt-2 rounded-full bg-amber-500/15 px-4 py-1.5 text-xs font-medium text-amber-300 group-hover:bg-amber-500/25 transition">
                        直接開始
                    </div>
                </button>

                {{-- 設定抽獎 --}}
                <button @@click="showCustomForm = true"
                    class="group relative flex flex-col items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-8 text-center transition hover:border-indigo-500/40 hover:bg-indigo-500/5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-500/10 text-2xl group-hover:bg-indigo-500/20 transition">
                        ⚙️
                    </div>
                    <div class="text-lg font-semibold text-white">設定抽獎</div>
                    <div class="text-xs text-white/45 leading-relaxed">
                        自訂名單、人數<br>與抽獎模式
                    </div>
                    <div class="mt-2 rounded-full bg-indigo-500/15 px-4 py-1.5 text-xs font-medium text-indigo-300 group-hover:bg-indigo-500/25 transition">
                        自訂設定
                    </div>
                </button>
            </div>

            {{-- 自訂表單 --}}
            <div x-show="showCustomForm"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="rounded-2xl border border-white/10 bg-white/5 p-6">

                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">自訂抽獎設定</h3>
                    <button @@click="showCustomForm = false" class="text-xs text-white/40 hover:text-white transition">
                        ← 返回
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- 名單 --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-white/70">參加者名單</label>
                        <textarea x-model="names" rows="5"
                            class="w-full rounded-xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white placeholder-white/25 outline-none focus:border-amber-500/50 transition resize-none"
                            placeholder="每行一個名字，或用逗號分隔&#10;例如：&#10;王大明&#10;陳小芳&#10;李美美"></textarea>
                        <p class="mt-1 text-xs text-white/30">留空將使用 30 位預設名單</p>
                    </div>

                    {{-- 每次人數 + 模式 --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-white/70">每次抽幾人</label>
                            <input type="number" x-model.number="drawCount" min="1" max="50"
                                class="w-full rounded-xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-amber-500/50 transition">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-white/70">抽獎模式</label>
                            <select x-model="drawMode"
                                class="w-full rounded-xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none focus:border-amber-500/50 transition appearance-none">
                                <option value="all_at_once">一次全抽</option>
                                <option value="one_by_one">逐一抽出</option>
                            </select>
                        </div>
                    </div>

                    {{-- 開始按鈕 --}}
                    <button @@click="startCustom()" :disabled="loading"
                        class="mt-2 w-full rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-3.5 text-sm font-bold text-black tracking-wide transition hover:from-amber-400 hover:to-amber-500 disabled:opacity-50">
                        <span x-text="loading ? '準備中...' : '開始抽獎'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== B) 齒輪按鈕 + 面板（抽獎中） ===== --}}
    <div x-show="!setupVisible" class="pointer-events-auto fixed left-4 top-4 z-50">
        {{-- 齒輪按鈕 --}}
        <button @@click="gearOpen = !gearOpen"
            class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-black/60 text-white/80 backdrop-blur-sm transition hover:bg-black/80 hover:text-white"
            aria-label="Demo 工具面板">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        </button>

        {{-- 面板 --}}
        <div x-show="gearOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2" @@click.outside="gearOpen = false"
            class="mt-2 w-56 rounded-xl border border-white/15 bg-black/80 p-4 shadow-2xl backdrop-blur-md">
            <div class="mb-3 text-xs font-semibold uppercase tracking-widest text-white/50">Demo 工具</div>

            <div class="space-y-1.5">
                {{-- 返回風格列表 --}}
                <a href="{{ url('/demo/lottery') }}"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm text-white/70 hover:bg-white/10 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                    </svg>
                    返回風格列表
                </a>

                {{-- 重新設定 --}}
                <button @@click="backToSetup()"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm text-white/70 hover:bg-white/10 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    重新設定
                </button>

                <div class="my-2 border-t border-white/10"></div>

                {{-- 重置抽獎 --}}
                <button @@click="resetDemo()" :disabled="loading"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-white/15 bg-white/5 px-3 py-2.5 text-sm font-medium text-white/80 transition hover:bg-white/10 hover:text-white disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="loading ? '處理中...' : '重置抽獎'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('demoStyleToolbar', () => ({
            setupVisible: true,
            showCustomForm: false,
            gearOpen: false,
            loading: false,
            names: '',
            drawCount: 5,
            drawMode: 'all_at_once',
            slug: @js($demoSlug),

            _csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.content
                    ?? window.LotteryConfig?.csrfToken ?? '';
            },

            async startDefault() {
                if (this.loading) return;
                this.loading = true;
                try {
                    const res = await fetch(`/demo/lottery/${this.slug}/reset`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this._csrfToken(),
                        },
                    });
                    if (!res.ok) return;
                    const payload = await res.json();
                    this.setupVisible = false;
                    if (window.__lotteryApplyPayload) {
                        window.__lotteryApplyPayload(payload);
                    }
                } catch (e) {
                    console.error('[demo-style-toolbar] startDefault error', e);
                } finally {
                    this.loading = false;
                }
            },

            async startCustom() {
                if (this.loading) return;
                this.loading = true;
                try {
                    const res = await fetch(`/demo/lottery/${this.slug}/configure`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this._csrfToken(),
                        },
                        body: JSON.stringify({
                            names: this.names,
                            draw_count: this.drawCount,
                            draw_mode: this.drawMode,
                        }),
                    });
                    if (!res.ok) return;
                    const payload = await res.json();
                    this.setupVisible = false;
                    this.showCustomForm = false;
                    if (window.__lotteryApplyPayload) {
                        window.__lotteryApplyPayload(payload);
                    }
                } catch (e) {
                    console.error('[demo-style-toolbar] startCustom error', e);
                } finally {
                    this.loading = false;
                }
            },

            async resetDemo() {
                if (this.loading) return;
                this.loading = true;
                try {
                    const res = await fetch(`/demo/lottery/${this.slug}/reset`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this._csrfToken(),
                        },
                    });
                    if (!res.ok) return;
                    const payload = await res.json();
                    if (window.__lotteryApplyPayload) {
                        window.__lotteryApplyPayload(payload);
                    }
                } catch (e) {
                    console.error('[demo-style-toolbar] resetDemo error', e);
                } finally {
                    this.loading = false;
                    this.gearOpen = false;
                }
            },

            backToSetup() {
                this.setupVisible = true;
                this.showCustomForm = false;
                this.gearOpen = false;
            },
        }));
    });
</script>
