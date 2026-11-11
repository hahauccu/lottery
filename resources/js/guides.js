import { trackDemoEvent } from './demo-metrics.js';

document.querySelectorAll('[data-copy-target]').forEach((button) => {
    const source = document.getElementById(button.dataset.copyTarget);
    const status = document.getElementById(`${button.dataset.copyTarget}-status`);
    if (!source || !status) return;
    button.hidden = false;
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(source.innerText.trim());
            status.textContent = '主持稿已複製，記得填入本場活動資訊。';
            trackDemoEvent('copy_click');
        } catch {
            status.textContent = '無法自動複製，請選取上方主持稿文字複製。';
        }
    });
});
