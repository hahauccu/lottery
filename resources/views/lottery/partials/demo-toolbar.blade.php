<div x-data="demoToolbar" class="fixed left-4 top-4 z-50" style="font-family: system-ui, -apple-system, sans-serif;">
    {{-- 齒輪按鈕 --}}
    <button @@click="open = !open"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-black/60 text-white/80 backdrop-blur-sm transition hover:bg-black/80 hover:text-white"
        aria-label="Demo 工具面板">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
    </button>

    {{-- 面板 --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2" @@click.outside="open = false"
        class="mt-2 w-64 rounded-xl border border-white/15 bg-black/80 p-4 shadow-2xl backdrop-blur-md">
        <div class="mb-3 text-xs font-semibold uppercase tracking-widest text-white/50">Demo 工具</div>

        {{-- 動畫風格選擇 --}}
        <div class="space-y-1">
            <template x-for="s in styles" :key="s.key">
                <button @@click="switchStyle(s.key)" :disabled="loading"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm transition" :class="currentStyle === s.key
                        ? 'bg-amber-500/20 text-amber-300 font-semibold'
                        : 'text-white/70 hover:bg-white/10 hover:text-white'">
                    <span class="h-2 w-2 flex-shrink-0 rounded-full"
                        :class="currentStyle === s.key ? 'bg-amber-400' : 'bg-white/20'"></span>
                    <span x-text="s.label"></span>
                </button>
            </template>
        </div>

        {{-- 分隔線 --}}
        <div class="my-3 border-t border-white/10"></div>

        {{-- 重置按鈕 --}}
        <button @@click="resetDemo" :disabled="loading"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-white/15 bg-white/5 px-3 py-2.5 text-sm font-medium text-white/80 transition hover:bg-white/10 hover:text-white disabled:opacity-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span x-text="loading ? '處理中...' : '重置抽獎'"></span>
        </button>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('demoToolbar', () => ({
            open: false,
            loading: false,
            currentStyle: window.LotteryConfig?.current_prize?.animation_style ?? 'lotto_air',
            styles: [
                { key: 'lotto_air', label: '樂透氣流機' },
                { key: 'red_packet', label: '紅包雨' },
                { key: 'scratch_card', label: '刮刮樂' },
                { key: 'treasure_chest', label: '寶箱' },
                { key: 'big_treasure_chest', label: '大寶箱' },
                { key: 'marble_race', label: '彈珠賽跑' },
            ],

            async switchStyle(style) {
                if (this.currentStyle === style || this.loading) return;
                this.loading = true;
                try {
                    const res = await fetch('/demo/lottery/style', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                ?? window.LotteryConfig?.csrfToken ?? '',
                        },
                        body: JSON.stringify({ style }),
                    });
                    if (!res.ok) return;
                    const payload = await res.json();
                    this.currentStyle = style;
                    if (window.__lotteryApplyPayload) {
                        window.__lotteryApplyPayload(payload);
                    }
                } catch (e) {
                    console.error('[demo-toolbar] switchStyle error', e);
                } finally {
                    this.loading = false;
                    this.open = false;
                }
            },

            async resetDemo() {
                if (this.loading) return;
                this.loading = true;
                try {
                    const res = await fetch('/demo/lottery/reset', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                ?? window.LotteryConfig?.csrfToken ?? '',
                        },
                    });
                    if (!res.ok) return;
                    const payload = await res.json();
                    if (window.__lotteryApplyPayload) {
                        window.__lotteryApplyPayload(payload);
                    }
                } catch (e) {
                    console.error('[demo-toolbar] reset error', e);
                } finally {
                    this.loading = false;
                    this.open = false;
                }
            },
        }));
    });
</script>