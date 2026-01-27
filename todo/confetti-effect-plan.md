# 撒花/彩帶特效實作計劃

> **狀態**: TODO - 待實作
> **建立日期**: 2026-01-25
> **相關檔案**: `resources/js/lottery.js`

## 效果說明

中獎時從畫面頂部灑下彩色彩帶，飄落到畫面底部消失。

## 技術方案

### 彩帶粒子屬性

```javascript
{
    x, y,           // 位置
    vx, vy,         // 速度
    width, height,  // 彩帶尺寸（長條形）
    rotation,       // 旋轉角度
    rotationSpeed,  // 旋轉速度
    hue,            // 顏色色相
    life,           // 生命週期
    t,              // 已存活時間
    wobble,         // 左右飄動相位
    wobbleSpeed,    // 飄動速度
}
```

### 視覺特點

- **形狀**：長條形彩帶（寬 8-15px，高 25-50px）
- **顏色**：多彩（紅、橙、黃、綠、藍、紫、金）
- **動態**：旋轉 + 左右飄動 + 緩慢下落
- **數量**：約 80-120 條
- **持續**：2-3 秒

---

## 實作步驟

### 1. 新增彩帶粒子陣列

在 `lottery.js` 的 `lottoAir` IIFE 內，`const particles = [];` 後新增：

```javascript
const confetti = [];
```

### 2. 新增 `launchConfetti()` 函數

```javascript
const launchConfetti = () => {
    const rect = lottoCanvasEl.getBoundingClientRect();
    const colors = [0, 30, 50, 120, 200, 280, 45]; // 紅橙黃綠藍紫金

    for (let i = 0; i < 100; i++) {
        confetti.push({
            x: rand(0, rect.width),
            y: rand(-50, -10),
            vx: rand(-50, 50),
            vy: rand(80, 200),
            width: rand(8, 15),
            height: rand(25, 50),
            rotation: rand(0, TAU),
            rotationSpeed: rand(-8, 8),
            hue: colors[Math.floor(rand(0, colors.length))],
            life: rand(2.5, 4),
            t: 0,
            wobble: rand(0, TAU),
            wobbleSpeed: rand(2, 5),
        });
    }
};
```

### 3. 新增 `updateConfetti()` 函數

```javascript
const updateConfetti = (dt) => {
    for (let i = confetti.length - 1; i >= 0; i--) {
        const c = confetti[i];
        c.t += dt;
        if (c.t >= c.life) {
            confetti.splice(i, 1);
            continue;
        }
        // 左右飄動
        c.wobble += c.wobbleSpeed * dt;
        c.x += c.vx * dt + Math.sin(c.wobble) * 30 * dt;
        c.y += c.vy * dt;
        // 旋轉
        c.rotation += c.rotationSpeed * dt;
        // 空氣阻力
        c.vx *= 0.99;
        c.vy *= 0.995;
    }
};
```

### 4. 新增 `drawConfetti()` 函數

```javascript
const drawConfetti = () => {
    const { ctx } = airState;
    if (!ctx || !confetti.length) return;

    confetti.forEach((c) => {
        const alpha = c.t < 0.5 ? 1 : Math.max(0, 1 - (c.t - c.life + 0.5) / 0.5);
        ctx.save();
        ctx.translate(c.x, c.y);
        ctx.rotate(c.rotation);
        ctx.globalAlpha = alpha * 0.9;
        ctx.fillStyle = `hsl(${c.hue} 90% 60%)`;
        ctx.fillRect(-c.width / 2, -c.height / 2, c.width, c.height);
        ctx.restore();
    });
    ctx.globalAlpha = 1;
};
```

### 5. 整合到更新與繪製流程

在 `update()` 函數中，`updateParticles(dt);` 後加入：

```javascript
updateConfetti(dt);
```

在 `drawFrame()` 函數中，`drawParticles();` 後加入：

```javascript
drawConfetti();
```

### 6. 觸發時機

找到球被抽出的邏輯（約第 700 行附近，`if (inChute) {` 區塊），在 `burst()` 後加入：

```javascript
if (inChute) {
    ball.out = true;
    // ... 現有程式碼 ...
    burst(ball.x, ball.y, 80, 260);
    launchConfetti();  // 新增這行
    // ...
}
```

### 7. 匯出 API（可選）

在 `lottoAir` 的 return 物件中加入，允許外部呼叫：

```javascript
return {
    // ... 現有 API ...
    launchConfetti,
};
```

---

## 驗證方式

1. 開啟抽獎頁面 `/{brandCode}/lottery`
2. 執行抽獎
3. 確認每次球被抽出時，畫面上方會灑下彩色彩帶
4. 彩帶應該會旋轉、飄動、緩慢下落
5. 約 2-3 秒後消失

---

## 效能考量

- 彩帶數量控制在 100-150 條
- 使用 `splice` 移除已消失的彩帶
- 不使用圖片，純 Canvas 繪製

---

## 相關程式碼位置

| 功能 | 位置 |
|------|------|
| 粒子系統 | `lottery.js` 約第 300-400 行 |
| 球被抽出邏輯 | `lottery.js` 約第 700 行 `if (inChute)` |
| 繪製流程 | `lottery.js` `drawFrame()` 函數 |
| 更新流程 | `lottery.js` `update()` 函數 |
