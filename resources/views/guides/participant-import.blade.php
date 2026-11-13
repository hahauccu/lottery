@extends('guides.layout', ['guideSlug' => 'lottery-participant-import'])
@section('guide-body-class', 'import-guide')
@push('guide-styles')
    @include('guides.partials.tour-styles')
@endpush

@section('actions')
    <div class="actions"><a class="guide-btn primary" href="#walkthrough">匯入步驟 <span aria-hidden="true">↓</span></a><a class="guide-btn" href="{{ asset('examples/lottery-participants-sample.csv') }}" download data-demo-event="download_click">下載示範名單 CSV ↓</a></div>
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'open-employees', 'label' => '開啟員工列表', 'text' => '在左側「員工管理」選擇「員工」，點選右上角「匯入 CSV」。', 'image' => '01-employees.jpg', 'alt' => '員工列表右上方的匯入 CSV 按鈕。', 'x' => 85, 'y' => 10],
            ['id' => 'upload-csv', 'label' => '上傳 CSV', 'text' => '選擇 CSV 檔案，或將檔案拖入上傳區。顯示「已上傳」後，點選「開始匯入」。', 'image' => '02-upload.jpg', 'alt' => 'CSV 檔案已上傳，下方顯示開始匯入按鈕。', 'x' => 20, 'y' => 43],
            ['id' => 'import-result', 'label' => '確認結果', 'text' => '匯入完成後，核對新增、更新與略過的筆數，並確認員工列表。本例新增 4 筆，更新與略過皆為 0 筆。', 'image' => '03-imported.jpg', 'alt' => '員工列表有四筆測試資料，通知顯示新增 4 筆、更新 0 筆、略過 0 筆。', 'x' => 69, 'y' => 7.5],
            ['id' => 'check-employee', 'label' => '核對資料', 'text' => '點選員工右側的「編輯」，確認姓名、Email、部門、員工編號與群組是否正確。', 'image' => '04-check-employee.jpg', 'alt' => '員工編輯頁的姓名、Email、部門、員工編號與群組欄位。', 'x' => 68, 'y' => 49],
        ];
    @endphp
    @include('guides.partials.screenshot-tour')

    <section class="guide-section sample-section" id="sample">
        <div class="section-title"><div><h2>範例名單</h2></div><a class="guide-btn" href="{{ asset('examples/lottery-participants-sample.csv') }}" download data-demo-event="download_click">下載示範 CSV ↓</a></div>
        <p>下載 CSV，以 Excel 開啟後填入員工資料，並保留第一列欄位名稱。</p>
        <div class="table-scroll" role="region" aria-label="可橫向捲動的示範名單" tabindex="0"><table><caption>四筆示範資料・電話欄位保留，內容留白</caption><thead><tr><th scope="col">姓名</th><th scope="col">Email</th><th scope="col">員工編號</th><th scope="col">部門</th><th scope="col">群組</th></tr></thead><tbody><tr><th scope="row">測試甲</th><td>demo001@example.com</td><td>DEMO001</td><td>企劃部</td><td>彩排甲組</td></tr><tr><th scope="row">測試甲</th><td>demo002@example.com</td><td>DEMO002</td><td>研發部</td><td>彩排乙組</td></tr><tr><th scope="row">測試乙</th><td>demo003@example.com</td><td>DEMO003</td><td>企劃部</td><td>彩排甲組</td></tr><tr><th scope="row">測試丙</th><td>demo004@example.com</td><td>DEMO004</td><td>研發部</td><td>彩排乙組</td></tr></tbody></table></div>
        <p class="small">測試信箱無法接收通知，正式使用請換成員工 Email。同一組織的員工名單供各活動共用。</p>
    </section>

    <section class="guide-section" id="columns">
        <h2>CSV 欄位說明</h2>
        <p>第一列保留欄位名稱，每列填一位員工。姓名與 Email 必須填寫；電話、部門的欄名也要保留，內容可以空白。完成後另存為 <strong>CSV UTF-8</strong>，再上傳到後台。</p>
        <div class="import-columns" aria-label="CSV 欄位總覽"><span>姓名 <small>必填</small></span><span>Email <small>必填</small></span><span>電話 <small>可留白</small></span><span>部門 <small>可留白</small></span><span>員工編號 <small>選填</small></span><span>群組 <small>選填</small></span></div>
        <p class="guide-note">目前匯入的是 CSV 檔，不能直接上傳 Excel 的 XLSX 檔。電話與員工編號若有前導 0，請先在 Excel 設為文字格式，另存後再核對。</p>
        <details class="import-faq"><summary>查看完整欄位對照</summary>
            <div class="table-scroll" role="region" aria-label="CSV 欄位對照" tabindex="0"><table><thead><tr><th scope="col">欄位</th><th scope="col">可用欄名</th><th scope="col">注意事項</th></tr></thead><tbody>
                <tr><th scope="row">姓名</th><td>姓名 / name</td><td>必須有值；同名者以 Email 區分。</td></tr>
                <tr><th scope="row">Email</th><td>Email / email</td><td>必須有值；匯入時轉小寫，用於同組織比對與更新。</td></tr>
                <tr><th scope="row">電話</th><td>電話 / phone</td><td>欄名必須保留，值可空白。</td></tr>
                <tr><th scope="row">部門</th><td>部門 / department</td><td>欄名必須保留；部門文字不等於抽獎群組。</td></tr>
                <tr><th scope="row">員工編號</th><td>員工編號 / employee_no</td><td>選填；更新既有員工時，省略或留白會清空原編號。</td></tr>
                <tr><th scope="row">群組</th><td>群組 / groups</td><td>選填；每筆只填一個群組，更新方式見下方說明。</td></tr>
            </tbody></table></div>
        </details>
    </section>

    <section class="guide-section" id="questions">
        <h2>常見問題</h2>
        <details class="import-faq"><summary>公司有兩位同名員工，會合併嗎？</summary><p>不會只因同名就合併。系統以同一組織內的 Email 辨識員工，上方兩位「測試甲」因 Email 不同而分開保存。員工編號可用於人工核對。</p></details>
        <details class="import-faq"><summary>同一份名單再匯入一次，會怎樣？</summary><p>同組織已有的 Email 會更新資料，不會新增第二筆。同一檔案內重複 Email 則只採第一筆，後續同 Email 列會略過。</p><p>更新會覆寫姓名、電話、部門與員工編號，不是只補空欄。空白電話、部門與編號會清空原值；省略員工編號欄也會清空原編號。再次匯入前先保留原始名單並核對欄位。</p></details>
        <details class="import-faq"><summary>群組留白，會保留原本的設定嗎？</summary><p>沒有「群組」欄位時，原群組不變；有欄位卻留白，會清空原群組。填一個群組名稱，會改成這一個群組，不存在的群組會建立。</p><p>一筆只能填一個群組。使用逗號、分號、頓號或直線分隔多個群組，該列會被略過。</p></details>
        <details class="import-faq"><summary>顯示「略過」或「CSV 欄位錯誤」，先查哪裡？</summary><p>欄位錯誤時，確認第一列有姓名、Email、電話、部門。若有略過，檢查姓名或 Email 是否空白、同檔 Email 是否重複，以及是否一筆填了多個群組。修正後再次匯入時，也要留意既有 Email 的更新行為。</p></details>
    </section>

    <section class="guide-section import-next" id="next">
        <h2>接著設定獎項</h2>
        <p>建立活動與獎項後，確認包含、排除條件與可抽人數。</p>
        <div class="actions"><a class="guide-btn primary" href="{{ route('guides.lottery-rules') }}">了解獎項資格與重複中獎規則 →</a><a class="guide-btn" href="{{ route('features.lottery-control') }}">看看後台如何切換獎項</a></div>
        <p class="small">只想先看抽獎效果？<a href="{{ route('demo.lottery.landing') }}" data-demo-event="play_click">免費試玩動畫</a>可以直接貼上姓名，不必準備 CSV。</p>
    </section>
    @include('guides.partials.image-dialog')
@endsection
