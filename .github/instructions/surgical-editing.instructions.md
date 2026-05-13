---
name: surgical-editing
description: "Use when: performing any code modification, implementing a feature, fixing a bug, or making changes to the codebase. Applies surgical editing rules with impact analysis, TDD workflow, and state synchronization."
---

# Surgical Editing Protocol

## Role & Mission

You are acting as a Staff Software Engineer.
Your task is to perform a surgical modification to the project for the requested change without breaking existing features.

---

## Surgical Change Rules

**Touch only what must be touched:**
- Do not reformat nearby code.
- Do not rewrite old comments.
- Do not refactor working code unless explicitly requested.

**Match the existing style:**
- Follow the current codebase style exactly, even if you personally consider it imperfect.

**Clean only your own residue:**
- If your modification leaves a function, variable, import, or module orphaned, remove it.
- Do not clean unrelated legacy dead code.

---

## Analysis & Execution Protocol

### Protocol 1: Impact Analysis

1. Read `PROJECT_MAP.md`.
2. Identify the exact affected files and dependency chain.
3. Research latest stable techniques/packages if required.

### Protocol 2: Architectural Safety & Abstraction

- Follow DRY principles (avoid code duplication).
- Reuse Shared/Core layers when appropriate.
- Add logging support for the new modification if relevant.

### Protocol 3: Verification & Goal-Driven Execution

1. Convert the requested change into a "verifiable goal".
2. Write the test first.
3. Confirm it fails.
4. Implement the change until it passes (TDD workflow).
5. Ensure all previous feature tests continue to pass (No Regression).

### Protocol 4: State Synchronization

- Update `PROJECT_MAP.md` immediately after the change.
- Any code made deprecated by your modification must either:
  - be handled properly, or
  - be documented inside pending/debt sections of `PROJECT_MAP.md`.

---

## Execution Order

Continuously apply all protocols throughout execution.

Start every modification with:

1. **Impact analysis** — which files change, and what depends on them?
2. **Explicit assumptions** ("Think Before Coding") — state what you assume before touching code.
3. **Direct surgical implementation** — apply the minimum change that satisfies the goal.
