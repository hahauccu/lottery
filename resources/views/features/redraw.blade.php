@extends('features.layout', ['featureSlug' => 'absent-redraw'])

@section('content')
    <section class="guide-section" id="workflow">
        <h2>勾選不在場者，釋出名額再補抽</h2>
        <p>唱名後未到場，可依活動規則在後台處理「不在場重抽」。選擇未領獎的中獎者、確認釋出後，再從前台補抽空出的名額。</p>
        <div class="feature-panel">
            <div class="feature-panel-title"><strong>不在場重抽</strong><span>操作流程示意</span></div>
            <ol class="feature-flow"><li><strong>01　勾選人員</strong>選擇不在場且尚未領獎的中獎者。</li><li><strong>02　確認釋出</strong>核對名單後，釋出中獎名額。</li><li><strong>03　前台補抽</strong>確認目前是同一獎項，再從前台補抽。</li></ol>
        </div>
        <ol><li><strong>確認缺席。</strong>唱名次數與等待時間，由主辦單位依事前公告判定。</li><li><strong>開啟「不在場重抽」。</strong>該獎項的有效中獎名額抽滿後，後台會顯示此入口。</li><li><strong>勾選並確認。</strong>已領獎、已釋出的紀錄不能再選取。</li><li><strong>回到前台補抽。</strong>釋出不會自動產生替補者。先確認「開放抽獎」已開啟、目前獎項就是剛釋出的獎項；若已切到別的獎，先切回，再從前台補抽。</li></ol>
    </section>
    <section class="guide-section" id="history">
        <h2>保留原中獎與替補紀錄</h2>
        <p>釋出後，原紀錄會保留釋出時間與原因。補抽的新紀錄則會對應原得獎者，方便查詢誰被釋出、由誰替補。</p>
        <div class="feature-panel">
            <div class="feature-panel-title"><strong>幸運獎：3 位中獎者，其中 1 位不在場</strong><span>示例資料</span></div>
            <dl class="feature-record"><dt>原第 2 位</dt><dd>測試甲不在場，紀錄標記「已釋出」。</dd><dt>剩餘名額</dt><dd>有效中獎者剩下 2 位，可補抽 1 位。</dd><dt>補抽結果</dt><dd>測試丁補上第 2 位，紀錄對應測試甲的原中獎紀錄。</dd></dl>
        </div>
        <p>中獎名單匯出包含原始中獎、已釋出與重抽補位等狀態。若替補者也不在場，後續再次釋出與補抽的關係同樣會保留。</p>
    </section>
    <section class="guide-section" id="conditions">
        <h2>釋出與補抽條件</h2>
        <div class="table-scroll" tabindex="0" role="region" aria-label="釋出條件"><table><caption>各種狀態的處理方式</caption><thead><tr><th scope="col">狀態</th><th scope="col">處理方式</th></tr></thead><tbody><tr><th scope="row">未領獎、未釋出</th><td>可以勾選，確認後釋出。</td></tr><tr><th scope="row">已領獎或已釋出</th><td>不能再用此流程釋出。</td></tr><tr><th scope="row">前台抽獎中</th><td>需等待抽獎結束，才能釋出名額。</td></tr><tr><th scope="row">逐一抽出</th><td>每次補抽一位；一次全抽則依剩餘名額與資格補抽。</td></tr><tr><th scope="row">可抽人數不足</th><td>不允許同獎項重複時，最多抽出剩餘可抽人數。</td></tr></tbody></table></div>
        <div class="guide-note"><strong>原中獎者還能再參加嗎？</strong><p>同一獎項若不允許重複中獎，已釋出者不會再次中獎；若允許重複，仍可能抽中。其他獎項則依各自的資格設定與當下中獎狀態判定。</p></div>
        <p>活動前可參考<a href="{{ route('guides.lottery-rules') }}">重複中獎與不在場重抽設定教學</a>，確認規則後再向參加者公告。</p>
    </section>
    <section class="guide-section feature-faq" id="questions">
        <h2>常見問題</h2>
        <details><summary>按下「不在場重抽」就會直接抽出新人嗎？</summary><p>不會。需先勾選人員、確認釋出，再確認前台是同一獎項後開始補抽。</p></details>
        <details><summary>已領獎者可以釋出嗎？</summary><p>不可以。此功能只處理尚未領獎、也尚未釋出的中獎紀錄。</p></details>
        <details><summary>釋出後，原兌獎連結還能使用嗎？</summary><p>原連結會顯示該筆中獎紀錄已釋出，無法再以此紀錄完成兌獎。</p></details>
    </section>
@endsection
