document.addEventListener('DOMContentLoaded', () => {
    const config = window.DanmakuConfig;
    if (!config || !config.danmakuEnabled) return;

    const form = document.getElementById('danmaku-form');
    const emailInput = document.getElementById('danmaku-email');
    const messageInput = document.getElementById('danmaku-message');
    const submitBtn = document.getElementById('danmaku-submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnCooldown = document.getElementById('btn-cooldown');
    const cooldownSecondsEl = document.getElementById('cooldown-seconds');
    const emailError = document.getElementById('email-error');
    const messageError = document.getElementById('message-error');
    const formSuccess = document.getElementById('form-success');

    let cooldownTimer = null;

    const getCooldownEndTime = () => {
        const key = `danmaku_cd_${config.brandCode}`;
        const stored = localStorage.getItem(key);
        return stored ? parseInt(stored, 10) : 0;
    };

    const setCooldownEndTime = (seconds) => {
        const key = `danmaku_cd_${config.brandCode}`;
        const endTime = Date.now() + seconds * 1000;
        localStorage.setItem(key, endTime.toString());
    };

    const updateCooldownUI = () => {
        const endTime = getCooldownEndTime();
        const now = Date.now();

        if (endTime > now) {
            const remaining = Math.ceil((endTime - now) / 1000);
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            btnCooldown.classList.remove('hidden');
            cooldownSecondsEl.textContent = remaining;
            cooldownTimer = setTimeout(updateCooldownUI, 1000);
        } else {
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnCooldown.classList.add('hidden');
            if (cooldownTimer) clearTimeout(cooldownTimer);
        }
    };

    updateCooldownUI();

    const clearErrors = () => {
        emailError.classList.add('hidden');
        messageError.classList.add('hidden');
        formSuccess.classList.add('hidden');
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();

        if (getCooldownEndTime() > Date.now()) return;

        const email = emailInput.value.trim();
        const message = messageInput.value.trim();
        if (!email || !message) return;

        try {
            const response = await fetch(config.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify({ email, message }),
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    if (data.errors.email) {
                        emailError.textContent = data.errors.email[0];
                        emailError.classList.remove('hidden');
                    }
                    if (data.errors.message) {
                        messageError.textContent = data.errors.message[0];
                        messageError.classList.remove('hidden');
                    }
                } else if (data.message) {
                    messageError.textContent = data.message;
                    messageError.classList.remove('hidden');
                }
                return;
            }

            formSuccess.textContent = '彈幕已發送！';
            formSuccess.classList.remove('hidden');
            messageInput.value = '';

            setCooldownEndTime(data.cooldown_seconds || 10);
            updateCooldownUI();

            setTimeout(() => formSuccess.classList.add('hidden'), 3000);
        } catch (error) {
            console.error('[danmaku] send error:', error);
            messageError.textContent = '發送失敗，請稍後再試';
            messageError.classList.remove('hidden');
        }
    });
});
