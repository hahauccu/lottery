    <section class="import-tour" id="walkthrough" data-guide-tour aria-label="操作流程">
        <nav class="tour-steps" aria-label="操作步驟">
            @foreach ($steps as $step)
                <a href="#{{ $step['id'] }}" data-tour-link><span class="tour-number">0{{ $loop->iteration }}</span><span>{{ $step['label'] }}</span></a>
            @endforeach
        </nav>
        @foreach ($steps as $step)
            <section class="tour-panel" id="{{ $step['id'] }}" data-tour-step aria-label="{{ $step['label'] }}">
                <div class="tour-copy"><p>{{ $step['text'] }}</p></div>
                <figure class="tour-figure">
                    <a class="tour-image-link" href="{{ asset(($imageDirectory ?? 'images/guides/participant-import').'/'.$step['image']) }}" data-guide-image data-image-caption="{{ $step['label'] }}" aria-label="放大：{{ $step['label'] }}的操作畫面">
                        <img src="{{ asset(($imageDirectory ?? 'images/guides/participant-import').'/'.$step['image']) }}" alt="{{ $step['alt'] }}" width="{{ $step['width'] ?? 1280 }}" height="{{ $step['height'] ?? 720 }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async">
                        @if (isset($step['x'], $step['y']))<span class="tour-pin" style="--pin-x:{{ $step['x'] }}%;--pin-y:{{ $step['y'] }}%" aria-hidden="true">{{ $loop->iteration }}</span>@endif
                        <span class="tour-zoom" aria-hidden="true">＋ 放大畫面</span>
                    </a>
                </figure>
            </section>
        @endforeach
        <div class="tour-controls" data-tour-controls hidden><button class="guide-btn" type="button" data-tour-prev>← 上一步</button><span role="status" aria-live="polite" data-tour-status></span><button class="guide-btn primary" type="button" data-tour-next>下一步 →</button></div>
    </section>
