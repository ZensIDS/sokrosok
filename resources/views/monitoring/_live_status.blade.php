<span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pl-1 pr-3 text-xs font-semibold text-slate-500 shadow-sm">
    <button id="live-refresh" type="button" title="Perbarui sekarang" aria-label="Perbarui sekarang"
            class="flex h-6 w-6 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
        <svg id="live-icon" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 11-3-6.7M21 4v5h-5"/></svg>
    </button>
    <span id="live-dot" class="h-2 w-2 rounded-full bg-emerald-500"></span>
    <span id="live-text" class="whitespace-nowrap">Diperbarui {{ now()->format('H:i:s') }}</span>
    <span class="hidden h-1 w-14 overflow-hidden rounded-full bg-slate-200 sm:block"><span id="live-bar" class="block h-full w-0 rounded-full bg-brand-500"></span></span>
    <span id="live-count" class="hidden w-12 text-right tabular-nums text-slate-400 sm:inline"></span>
</span>