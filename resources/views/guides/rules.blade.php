@extends('guides.layout', ['guideSlug' => 'lottery-rules'])

@section('actions')
    <div class="actions"><a class="guide-btn primary" href="#compare">先看規則對照</a><a class="guide-btn" href="{{ route('guides.lottery-host-script') }}">準備主持公告</a></div>
@endsection

@section('content')
    <section class="guide-section" id="compare">
        <h2>1. 「同一獎項」與「跨獎項」，是兩個設定問題。</h2>
        <p>假設測試甲已中一般獎：他能否再中同一個一般獎，與他能否參加稍後的壓軸獎，需要分別確認。</p>
        <div class="table-scroll" role="region" aria-label="可橫向捲動的重複中獎設定比較" tabindex="0"><table><caption>用活動需求對照設定位置</caption><thead><tr><th scope="col">想要的規則</th><th scope="col">設定重點</th><th scope="col">彩排驗證</th></tr></thead><tbody>
            <tr><th scope="row">同一獎項每人限中一次</th><td>關閉該獎項「同一獎項可重複中獎」。</td><td>逐一抽出兩輪，核對同一人不再取得該獎項名額。</td></tr>
            <tr><th scope="row">同一獎項可中多次</th><td>開啟「同一獎項可重複中獎」；每個名額都從合格人選抽選。</td><td>用少量合成名單測試，確認重複得主符合事先公告。</td></tr>
            <tr><th scope="row">中過任何獎就不參加後續獎</th><td>後續各獎項的「排除群組」選入活動的「已中獎員工」。</td><td>抽完一般獎後，確認後續獎項可抽人數減少且名單符合預期。</td></tr>
            <tr><th scope="row">僅排除某個前置獎項得主</th><td>使用對應的「獎項名稱中獎員工」系統群組作為排除條件；獎項抽完後核對。</td><td>確認只有指定前置獎項得主被排除。</td></tr>
        </tbody></table></div>
        <p class="guide-note">只關閉同獎項重複，不會自動排除跨獎項的得主。後續每一個獎項都要核對資格設定。</p>
    </section>
    <section class="guide-section" id="eligibility">
        <h2>2. 先有包含資格，再套用排除條件。</h2>
        <p>在獎項設定的資格區，<strong>包含群組與包含員工採聯集，排除優先</strong>。未選任何包含群組或員工，可抽人數會是 0，不會自動視為所有人。</p>
        <ol><li><strong>設定包含：</strong>全體員工、指定群組或個別員工，依實際規則選擇。</li><li><strong>設定排除：</strong>排除不適用的人員、群組，或依規則排除已中獎員工。</li><li><strong>看實際可抽人數：</strong>抽過前面獎項、變更群組或釋出紀錄後，再核對後續獎項。</li></ol>
        <p>例：包含「企劃組」與測試乙，但排除「已中獎員工」。若測試乙已中獎，仍會被排除；額外選入包含員工不會抵銷排除條件。</p>
        <p>一次全抽會抽選該獎項剩餘名額；未允許同獎項重複時，可抽人數不足，實際得主數也可能少於設定名額。請在開始前核對，不要等到動畫結束才發現。</p>
    </section>
    <section class="guide-section" id="redraw">
        <h2>3. 不在場重抽，先釋出名額，再補抽。</h2>
        <p>活動後台的獎項列表，在獎項名額已抽完時提供「不在場重抽」入口。依主辦單位公告核對人員後，處理仍有效且尚未領獎的中獎紀錄。</p>
        <ol><li><strong>確認缺席：</strong>主持人依公告唱名，交由固定窗口確認。</li><li><strong>核對原紀錄：</strong>確認正確活動、獎項與得主，並確認該紀錄尚未領獎。</li><li><strong>釋出名額：</strong>透過不在場重抽流程處理；前台抽獎中不可釋出，先等本輪結束。</li><li><strong>重新確認資格：</strong>核對剩餘名額與可抽人員，再由前台開始補抽。</li><li><strong>核對替補：</strong>補抽會沿用空出的順序名額，保留原紀錄及與替補的關係。</li></ol>
        <p><strong>釋出不會刪掉歷史中獎紀錄，也不等於把該員工全場禁抽。</strong>系統的「已中獎員工」群組依未釋出的紀錄計算；其他獎項是否仍可抽到該員工，取決於剩餘有效紀錄與包含、排除設定。若活動規則要求缺席者後續都不參加，請另行設定排除並核對。</p>
        <p>本流程是對已確認缺席的紀錄進行處理。畫面短暫停住時，先核對後台結果，再決定是否需要操作。</p>
    </section>
    <section class="guide-section" id="announce">
        <h2>4. 公告規則，用兩輪彩排驗證。</h2>
        <blockquote><p>本活動每位參加者在同一獎項中＿＿；已中獎者對後續獎項的資格為＿＿。得主經唱名＿＿後，由＿＿確認是否到場；確認缺席時依＿＿方式釋出及補抽。領獎請到＿＿核對。</p></blockquote>
        <ul><li>第一輪用一般獎測試同獎項是否重複，核對名額與有效中獎紀錄。</li><li>第二輪切換後續獎項，核對跨獎項排除是否符合公告。</li><li>另用測試紀錄走一次缺席、釋出與替補流程，核對原紀錄仍可追查。</li></ul>
        <p>免費 Demo 提供名單、人數與動畫試玩；正式活動的員工資格、系統群組、領獎與釋出流程需在組織後台設定。</p>
        <div class="actions"><a class="guide-btn" href="{{ route('guides.lottery-host-script') }}">把規則接到主持口播 →</a><a class="guide-btn" href="{{ route('guides.lottery-participant-import') }}">先整理名單與群組</a></div>
    </section>
@endsection
