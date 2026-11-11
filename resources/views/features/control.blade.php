@extends('features.layout', ['featureSlug' => 'lottery-control'])

@section('content')
    <section class="guide-section" id="division">
        <h2>後台選獎，前台顯示抽獎畫面</h2>
        <p>工作人員可從後台選擇目前獎項、返回預覽，並查看前台狀態。投影前台顯示對應的獎項、動畫與中獎結果，適合主持人與後台人員分工操作。</p>
        <div class="feature-panel">
            <div class="feature-panel-title"><strong>獎項切換與開抽</strong><span>操作流程示意</span></div>
            <ol class="feature-flow"><li><strong>後台選擇獎項</strong>設定目前獎項，或切回預覽畫面。</li><li><strong>確認前台畫面</strong>等待切換完成，核對獎項名稱與設定。</li><li><strong>前台開始抽獎</strong>配合主持人倒數，由現場操作人員開抽。</li></ol>
        </div>
        <div class="table-scroll" tabindex="0" role="region" aria-label="後台與前台分工"><table><caption>操作分工</caption><thead><tr><th scope="col">後台</th><th scope="col">前台</th></tr></thead><tbody><tr><td>管理活動、獎項、名單與資格</td><td>顯示目前獎項的動畫與中獎結果</td></tr><tr><td>選擇目前獎項、返回預覽、設定「開放抽獎」</td><td>以抽獎按鈕或 Enter／空白鍵開始抽獎</td></tr><tr><td>查看前台就緒、抽獎中與切換中狀態</td><td>回報操作狀態與切換結果</td></tr></tbody></table></div>
    </section>
    <section class="guide-section" id="workflow">
        <h2>現場操作步驟</h2>
        <ol><li><strong>開啟前台。</strong>從活動後台點選「前台抽獎」，確認投影畫面正常。</li><li><strong>等待就緒。</strong>查看後台狀態列，確認前台在線，且沒有正在進行的抽獎。</li><li><strong>選擇獎項。</strong>在獎項列表點選「切換本輪抽獎」並確認。要返回預覽時，點選「切換到預覽輪播」。</li><li><strong>開放並確認。</strong>開啟活動的「開放抽獎」，在後台核對可抽名單，確認前台顯示正確獎項後，再開始抽獎。</li><li><strong>繼續下一獎。</strong>揭獎完成後，可切換下一獎、返回預覽，或處理<a href="{{ route('features.absent-redraw') }}">不在場重抽</a>。</li></ol>
        <aside class="feature-example"><p><strong>分工範例：</strong>後台選好「幸運獎」，確認前台畫面後通知主持人。主持人倒數，再由前台操作人員按下抽獎。同一人也可以兼任操作，依現場人力安排即可。</p></aside>
    </section>
    <section class="guide-section" id="connection">
        <h2>操作前請確認</h2>
        <ul><li><strong>活動已開放抽獎。</strong>新活動預設關閉，請先開啟「開放抽獎」。</li><li><strong>前台保持開啟與連線。</strong>離線時，需先恢復連線才能切換獎項或預覽。</li><li><strong>抽獎中不能切換。</strong>請等待該輪抽獎完成，再切換獎項或返回預覽。</li><li><strong>切換完成後才能抽獎。</strong>尚未完成切換確認時，系統會暫時阻擋開抽。</li><li><strong>獎項需有可抽者。</strong>沒有設定包含對象，或排除後無人可抽，就無法設為目前獎項。</li></ul>
        <p>畫面更新受現場網路與伺服器狀況影響。活動前請使用實際名單、現場網路與投影設備測試。</p>
    </section>
    <section class="guide-section feature-faq" id="questions">
        <h2>常見問題</h2>
        <details><summary>能從後台直接開始抽獎嗎？</summary><p>目前後台管理獎項、預覽與活動狀態，正式抽獎仍由前台觸發。選好獎項後，需由前台操作人員開始抽獎。</p></details>
        <details><summary>設定活動時間後，會自動開抽嗎？</summary><p>不會。「抽獎開始時間」欄位不會自動觸發抽獎，仍需由人員從前台操作。</p></details>
        <details><summary>前台離線或一直停在切換中，怎麼處理？</summary><p>先檢查前台分頁與網路是否正常、是否仍在抽獎。恢復連線後，確認前後台的目前獎項與中獎紀錄一致，再繼續操作。</p></details>
    </section>
@endsection
