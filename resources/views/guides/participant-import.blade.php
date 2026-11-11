@extends('guides.layout', ['guideSlug' => 'lottery-participant-import'])

@section('actions')
    <div class="actions"><a class="guide-btn primary" href="{{ asset('examples/lottery-participants-sample.csv') }}" download data-demo-event="download_click">下載合成名單 CSV ↓</a><a class="guide-btn" href="#columns">先看欄位說明</a></div>
@endsection

@section('content')
    <section class="guide-section" id="sample">
        <h2>1. 先用四筆合成資料，走一次匯入。</h2>
        <p>本站員工後台目前提供<strong>「匯入 CSV」</strong>。名單可以在 Excel 整理，完成後另存為 UTF-8 CSV 再上傳；本教學不以直接上傳 XLSX 作為操作步驟。</p>
        <p>範例包含四筆資料，刻意放入兩位同名「測試甲」，但使用不同 Email 和員工編號，方便觀察辨識方式。電話欄位保留但值留空。</p>
        <div class="table-scroll" role="region" aria-label="可橫向捲動的合成名單預覽" tabindex="0"><table><caption>範例中的四位合成參加者</caption><thead><tr><th scope="col">姓名</th><th scope="col">Email</th><th scope="col">員工編號</th><th scope="col">部門／群組</th></tr></thead><tbody><tr><th scope="row">測試甲</th><td>demo001@example.com</td><td>DEMO001</td><td>企劃部／彩排甲組</td></tr><tr><th scope="row">測試甲</th><td>demo002@example.com</td><td>DEMO002</td><td>研發部／彩排乙組</td></tr><tr><th scope="row">測試乙</th><td>demo003@example.com</td><td>DEMO003</td><td>企劃部／彩排甲組</td></tr><tr><th scope="row">測試丙</th><td>demo004@example.com</td><td>DEMO004</td><td>研發部／彩排乙組</td></tr></tbody></table></div>
        <p class="guide-note">範例使用不供實際收件的 example.com 信箱。請在測試用組織或彩排名單操作；正式活動前換成實際通知資料，再檢查每個獎項的資格。</p>
    </section>
    <section class="guide-section" id="columns">
        <h2>2. 四個必要欄位，兩個選填欄位。</h2>
        <p>「必要欄位」指 CSV 第一列必須有該欄名。每筆姓名與 Email 也必須有值；電話與部門的欄位要保留，但單筆值可留空。</p>
        <div class="table-scroll" role="region" aria-label="可橫向捲動的匯入欄位說明" tabindex="0"><table><caption>CSV 欄位與處理方式</caption><thead><tr><th scope="col">欄位</th><th scope="col">CSV 標題</th><th scope="col">填寫方式</th></tr></thead><tbody>
            <tr><th scope="row">姓名</th><td><code>姓名</code> 或 <code>name</code></td><td>必須有欄位與值；同名者仍以 Email 區分。</td></tr>
            <tr><th scope="row">Email</th><td><code>Email</code> 或 <code>email</code></td><td>必須有欄位與值；匯入時轉為小寫，用於同一組織內比對與更新。</td></tr>
            <tr><th scope="row">電話</th><td><code>電話</code> 或 <code>phone</code></td><td>必須有欄位，值可空白；在 Excel 以文字格式保留前導 0。</td></tr>
            <tr><th scope="row">部門</th><td><code>部門</code> 或 <code>department</code></td><td>必須有欄位，值可空白；部門文字與抽獎群組是不同欄位。</td></tr>
            <tr><th scope="row">員工編號</th><td><code>員工編號</code> 或 <code>employee_no</code></td><td>選填，用於人工核對；更新既有員工時，若省略此欄或留白，原編號會被清空。</td></tr>
            <tr><th scope="row">群組</th><td><code>群組</code> 或 <code>groups</code></td><td>選填；每筆只接受一個群組，且會取代該員工既有群組。</td></tr>
        </tbody></table></div>
    </section>
    <section class="guide-section" id="import">
        <h2>3. 從 Excel 整理，到後台核對。</h2>
        <ol><li><strong>下載 CSV：</strong>保留第一列標題，在每列填入一位參加者。</li><li><strong>檢查識別資料：</strong>確認姓名、Email 有值，Email、部門與群組沒有多餘空白或錯字。</li><li><strong>另存 UTF-8 CSV：</strong>若用 Excel 編輯，另存前先確認文字編碼與電話、編號的格式。</li><li><strong>進入正確組織：</strong>在員工列表選「匯入 CSV」，上傳檔案並開始匯入。</li><li><strong>閱讀結果：</strong>核對新增、更新、略過的數量，再查看幾筆員工的 Email、部門與群組。</li><li><strong>確認獎項資格：</strong>匯入員工不等於所有獎項都已包含這些人；到獎項設定檢查可抽人數。</li></ol>
        <p>如果目的是先看動畫，免費 Demo 可以直接貼上姓名，不需要準備 Email 或匯入檔案。CSV 教學是正式組織後台的操作流程。</p>
    </section>
    <section class="guide-section" id="duplicates">
        <h2>4. 同名可以並存，重複 Email 會影響更新。</h2>
        <ul><li><strong>同名、不同 Email：</strong>會視為不同員工；以員工編號或其他資料輔助現場核對。</li><li><strong>同一檔案重複 Email：</strong>只採第一筆，後續同 Email 列會略過。先確認第一筆的姓名與群組才是要保留的資料。</li><li><strong>同組織已存在該 Email：</strong>更新姓名、電話、部門、員工編號；其中空白電話、部門與員工編號會更新為空值；即使整個員工編號欄不存在，原編號也會被清空。</li><li><strong>姓名或 Email 空白：</strong>該列會略過。清理重複前，也要先移除空白或不完整列。</li></ul>
        <p>匯入不是「只補空欄」；再次上傳前，先核對會覆寫的欄位，並保留活動前確認過的名單版本。</p>
    </section>
    <section class="guide-section" id="groups">
        <h2>5. 群組欄位，三種情況要分開看。</h2>
        <div class="table-scroll" role="region" aria-label="可橫向捲動的群組匯入規則" tabindex="0"><table><caption>群組資料的實際更新效果</caption><thead><tr><th scope="col">檔案情況</th><th scope="col">更新效果</th></tr></thead><tbody><tr><th scope="row">沒有群組欄位</th><td>不修改該員工既有群組。</td></tr><tr><th scope="row">有欄位，這列留空</th><td>清空該員工既有群組。</td></tr><tr><th scope="row">填一個群組名稱</th><td>改為這一個群組；不存在的同組織群組會建立。</td></tr><tr><th scope="row">填多個群組名稱</th><td>該列會略過；不可用逗號、分號、頓號或直線分隔來一次指定多個群組。</td></tr></tbody></table></div>
        <p>例如，只想更新員工編號而保留所有群組時，可以移除整個群組欄位；保留欄位卻把值清空，會有不同結果。</p>
    </section>
    <section class="guide-section" id="errors">
        <h2>6. 匯入完成，再核對這四件事。</h2>
        <ul><li>欄位錯誤時，檢查第一列是否包含姓名、Email、電話、部門。</li><li>略過數量較多時，檢查空白姓名、空白或重複 Email，以及多群組值。</li><li>員工資料有更新時，抽查原有欄位是否被空值覆寫。</li><li>到每個獎項確認包含、排除資格與可抽人數，再用測試名單彩排。</li></ul>
        <div class="actions"><a class="guide-btn" href="{{ route('guides.lottery-rules') }}">接著設定抽獎資格與重複規則 →</a><a class="guide-btn" href="{{ route('demo.lottery.landing') }}" data-demo-event="play_click">先用 Demo 試玩動畫</a></div>
    </section>
@endsection
