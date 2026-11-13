@extends('guides.layout')
@section('guide-body-class', 'import-guide flow-guide')
@push('guide-styles')
    @include('guides.partials.tour-styles')
@endpush
@section('actions')
    @if (isset($walkthrough['download']))
        <div class="actions"><a class="guide-btn" href="{{ asset($walkthrough['download']) }}" download data-demo-event="download_click">下載範例分析報表 Excel ↓</a></div>
    @endif
@endsection
@section('content')
    @php
        $steps = collect($walkthrough['steps'])->map(function ($step, $index) {
            [$width, $height] = getimagesize(public_path('images/guides/lottery-flow/'.$step[2]));
            return ['id' => 'operation-'.($index + 1), 'label' => $step[0], 'text' => $step[1], 'image' => $step[2], 'alt' => $step[0].'：'.$step[1], 'width' => $width, 'height' => $height];
        })->all();
    @endphp
    @include('guides.partials.screenshot-tour', ['imageDirectory' => 'images/guides/lottery-flow'])
    <section class="guide-section" id="notes"><h2>使用提醒</h2><p>{{ $walkthrough['note'] }}</p>
        @if ($guideSlug === 'lottery-claims-export')<p><a href="{{ route('guides.lottery-qr-claim') }}">查看 QR Code 查驗與領獎步驟 →</a></p>@endif
    </section>
    @include('guides.partials.image-dialog')
@endsection
