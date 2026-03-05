# 🌀 戰鬥陀螺 — BattleTop 抽獎動畫設計規劃

## 概念

**陀螺對決式淘汰賽**。所有參賽陀螺從舞台四周衝入圓形競技場，
相互碰撞、旋轉、互相撞飛——只有最後倖存（仍在場內旋轉）的 N 個陀螺中獎。

---

## 動畫流程

```
待機       → 倒數 3-2-1 → 入場       → 混戰     → 倖存者揭曉 → 結果
[陀螺待命]    [震动特效]   [從邊緣衝入]  [物理碰撞]  [最後N顆閃爍]  [名單顯示]
```

### 詳細階段說明

| 階段 | 時長 | 說明 |
|------|------|------|
| `idle` | 無限 | 舞台中央幾個展示陀螺緩緩旋轉 |
| `countdown` | 3s | 倒數 3-2-1，陀螺在場邊集合 |
| `entry` | 1-2s | 所有陀螺從四面衝入場地 |
| `battle` | holdSeconds | 撞擊混戰，失去角速度/出界的陀螺消滅 |
| `reveal` | 2s | 倖存者閃光、名字放大顯示 |
| `finished` | 5s | 停留顯示結果，再跳到得獎名單 |

---

## 物理設計

### 陀螺物理模型

```
每顆陀螺：
- 位置 (x, y)
- 速度 (vx, vy)
- 角速度 omega（重要！決定存活）
- 半徑 r
- 質量 mass
- 狀態 alive / defeated / winner
```

### 旋轉衰減（摩擦力）

```
每幀：omega *= (1 - friction * dt)
omega < threshold → 陀螺「失去動力」→ 觸發淘汰動畫（倒下）
```

### 碰撞處理

1. **彈性碰撞**（球對球）：動量守恆，碰撞後速度交換
2. **碰撞轉移角速度**：互撞後，較快的陀螺會「搶走」部分對方的角速度（強者吃弱者）
3. **出界淘汰**：陀螺中心超出圓形競技場邊界 → 標記為 `defeated`
4. **碰撞特效**：衝擊波粒子、火花

### 入場方式

```javascript
// 從場地邊緣隨機角度衝入
const angle = rand(0, TAU);
const spawnRadius = arenaRadius + margin;
top.x = cx + cos(angle) * spawnRadius;
top.y = cy + sin(angle) * spawnRadius;
// 速度指向中心，帶隨機偏移
const aimAngle = angle + PI + rand(-0.3, 0.3);
top.vx = cos(aimAngle) * entrySpeed;
top.vy = sin(aimAngle) * entrySpeed;
top.omega = rand(15, 30); // 初始高角速度
```

---

## 視覺設計

### 陀螺外觀

```
陀螺由三層組成（同心圓）：
1. 外圈（旋轉色環）：依 omega 速率旋轉，顏色隨陀螺個人色
2. 中圈（主體）：半透明、依角度旋轉的圖案
3. 中心點：固定顯示名字
```

### 顏色系統

```javascript
const TOP_COLORS = [
  '#f43f5e', '#f97316', '#eab308', '#22c55e',
  '#06b6d4', '#6366f1', '#a855f7', '#ec4899',
  '#10b981', '#3b82f6', '#f59e0b', '#ef4444',
];
```

### 狀態視覺效果

| 狀態 | 視覺 |
|------|------|
| `alive` | 正常旋轉，外圈高速轉動 |
| `struggling` | omega < 30%，陀螺搖晃（x 軸橢圓壓縮） |
| `defeated` | 慢速向外飛出 + 漸淡消失 + 崩解粒子 |
| `winner` | 外圈金色閃光，名字放大顯示，光環 |

### 競技場外觀

```
- 深色背景（#0d1117）
- 圓形競技場：淡灰邊框 + 內部紋路（六角格）
- 場地外：陰影漸層
- 角落顯示倖存者計數：「💫 剩餘 N 顆」
```

---

## 名字顯示

- 每顆陀螺中心：顯示 `shortLabel`（最多4字）
- 陀螺半徑隨人數動態縮放：`r = clamp(320 / sqrt(N), 18, 45)`
- 字體白色，隨陀螺旋轉（但文字不旋轉）

---

## 淘汰邏輯

```
存活條件 (同時滿足)：
1. omega > MIN_OMEGA (5 rad/s)
2. 位置在競技場圓形邊界內
3. 未被其他陀螺「擊飛」（速度閾值）

淘汰後處理：
- 標記 defeated = true
- 播放崩解動畫（持續 0.5s）
- 從物理模擬移除
```

---

## 中獎判定（winnerQueue 機制）

與圓球賽跑相同，使用 `winnerQueue` 確保名稱一致：

```
入場前：driver 呼叫 setWinnerQueue(winners.map(w => w.name))
結束時：依倖存順序從 queue 取出實際得獎者名字
        覆蓋到對應陀螺上
waitForNextPick()：等每顆倖存陀螺揭曉後 resolve
```

---

## 整合步驟（依照 skill 文件）

1. `[ ]` 建立 `battleTop` IIFE 模組
2. `[ ]` 新增 `isBattleTopStyle()` 判斷函數
3. `[ ]` 加入 `isCanvasStyle()`
4. `[ ]` 註冊 `animationDrivers['battle_top']`
5. `[ ]` 更新 `getAnimationDriver()`
6. `[ ]` 更新 `stopAllAnimations()`
7. `[ ]` 更新 `window resize` 處理
8. `[ ]` 後端 PrizesRelationManager 新增選項
9. `[ ]` Demo 頁面整合

---

## 技術挑戰

| 挑戰 | 解法 |
|------|------|
| 陀螺太多效能 | 限制最多 30 顆；物理步驟合批 |
| 碰撞偵測 O(n²) | n≤30 可接受；或用空間格子優化 |
| 出局動畫重疊 | 維護 `dying[]` 陣列，獨立繪製 |
| 角速度視覺化 | 外環旋轉速度與 omega 對應 |
| 人數很少時（1-3人）| 不打倒對方，改為「最長存活者」中獎 |

---

## 參數設計

```javascript
const BT = {
  arenaRadius:   0,      // 動態計算（螢幕最小邊 * 0.42）
  topRadius:     28,     // 動態：clamp(320 / sqrt(N), 18, 45)
  friction:      0.18,   // 每秒 omega 衰減率
  minOmega:      5,      // 低於此值觸發淘汰
  entrySpeed:    220,    // 入場速度 px/s
  collisionElasticity: 0.75,
  omegaTransfer: 0.15,   // 碰撞時轉移角速度的比例
  gravity:       0,      // 無重力（競技場水平）
};
```

---

## 開發里程碑

- **Phase 1**：完成 `BattleTop.html` 原型，驗證物理碰撞與視覺
- **Phase 2**：整合進 `lottery.js`，接 Driver 介面
- **Phase 3**：接上 winnerQueue / waitForNextPick
- **Phase 4**：後端選項 + Demo 頁面

---

## 參考

- 本系統圓球賽跑（`marbleRace` 模組）的 winnerQueue 實作
- `add-lottery-animation.skill.md` 整合步驟
