@extends('features.layout', ['featureSlug' => 'lottery-analysis'])

@section('content')
    <section class="guide-section" id="workflow">
        <h2>活動前先跑一次模擬</h2>
        <p>設定好名單與獎項後，可在後台執行多次模擬，查看各獎項與部門的統計結果。分析完成後可下載 Excel，提供福委會、行政或主管討論。</p>
        <div class="feature-panel">
            <div class="feature-panel-title"><strong>抽獎分析</strong><span>操作流程示意</span></div>
            <div class="feature-stat">1–1,000 <small>次／單次分析</small></div>
            <ol class="feature-flow"><li><strong>01　設定活動</strong>整理名單、部門資料與獎項資格。</li><li><strong>02　輸入次數</strong>預設 200 次，可設定 1 至 1,000 次。</li><li><strong>03　下載報表</strong>分析完成後，查看結果並下載 Excel。</li></ol>
        </div>
        <p>在活動編輯頁點選「抽獎分析」即可開始。畫面會顯示排隊與分析進度，完成後提供下載；所需時間依名單、獎項數量及伺服器狀況而定。</p>
    </section>
    <section class="guide-section" id="report">
        <h2>獎項統計與部門分布</h2>
        <div class="table-scroll" tabindex="0" role="region" aria-label="分析指標說明"><table><caption>報表內容</caption><thead><tr><th scope="col">項目</th><th scope="col">統計內容</th><th scope="col">用途</th></tr></thead><tbody>
            <tr><th scope="row">獎項統計</th><td>平均可抽人數與中獎次數</td><td>比較各獎項的模擬結果。</td></tr>
            <tr><th scope="row">部門比較</th><td>各部門的可抽占比、中獎占比及差異</td><td>查看部門在模擬中的分布。</td></tr>
            <tr><th scope="row">Excel 報表</th><td>總覽、獎項統計、部門比較與圖表等分頁</td><td>留存結果，或提供團隊討論。</td></tr>
        </tbody></table></div>
        <p>分析結果供活動規劃參考。名單或獎項調整後，可重新產出報表。</p>
    </section>
    <section class="guide-section" id="scope">
        <h2>適用範圍與注意事項</h2>
        <p>目前以指定員工、有成員的一般群組為主，請先確認參加對象與獎項名額。</p>
        <div class="guide-note"><strong>系統群組與動態資格尚未完整支援</strong><p>使用「全體員工」「已中獎員工」等系統群組，或依前面獎項的結果排除人員時，分析可能與正式資格不同。正式抽獎前，請在後台核對可抽名單。</p></div>
        <p>資格設定可參考<a href="{{ route('guides.lottery-rules') }}">包含、排除與重複中獎教學</a>。</p>
    </section>
    <section class="guide-section feature-faq" id="questions">
        <h2>常見問題</h2>
        <details><summary>一次可以模擬幾次？</summary><p>可設定 1 至 1,000 次。每次分析各自產出報表，多份結果不會自動合併。</p></details>
        <details><summary>模擬會影響正式中獎名單嗎？</summary><p>不會。分析結果獨立保存，不占用正式獎項名額，也不會寄出中獎通知。</p></details>
        <details><summary>模擬結果可以保證正式抽獎的結果嗎？</summary><p>不可以。模擬統計用於活動規劃參考，正式結果仍由實際抽獎產生，也不能用報表保證個人的中獎機會。</p></details>
    </section>
@endsection
