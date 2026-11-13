@php($series = \App\Support\LotteryWalkthroughs::series())
<nav class="guide-series" aria-label="完整抽獎流程">
    @foreach ($series as $slug => $label)
        <a href="{{ route('guides.'.$slug) }}" @if (($guideSlug ?? null) === $slug) aria-current="page" @endif><span>{{ $loop->iteration }}</span>{{ $label }}</a>
    @endforeach
</nav>
