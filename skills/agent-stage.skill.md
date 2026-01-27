---
name: agent-stage
description: Enforces 1080p stage layout and OBS-safe UI zones for lottery or event animations.
---

# Stage Layout

## Commands
agent-stage init
agent-stage set resolution 1920 1080
agent-stage set safeMargin 120
agent-stage add result-bar

## API
initStage(canvas)
drawResultBar(text)