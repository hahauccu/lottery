---
name: agent-particles
description: Adds a professional particle system to HTML Canvas or WebGL animations, including explosion effects, spark trails, glow blending, and performance constraints.
---

# Particle System with agent-particles

## Quick start
agent-particles init
agent-particles add explosion
agent-particles add trail

## Commands
agent-particles init
agent-particles add explosion
agent-particles add trail
agent-particles add spark
agent-particles set maxParticles 1200

## Injected API
emitExplosion(x,y,power,color)
updateParticles(dt)
drawParticles(ctx)