# CLAUDE.md

Agency / CRM management system. Laravel 12 + Livewire 4, PHP 8.5, SQLite.

## Persistent memory (read first, every session)

Memory lives in `.claude/memory/`. It survives session close. Use it before rereading the repo.

**Session start — do in this order:**
1. Read `.claude/memory/current-task.md`.
2. Read `.claude/memory/context.md`.
3. Run `git status` and `git diff --stat`.
4. Read `.claude/memory/architecture.md` or `decisions.md` only if the task needs them.
5. Read source files only when the current task requires their contents. Do not reread a file just because a past session read it.

**During work:**
- Open only files relevant to the current task. Do not reread an unchanged file.
- Prefer `git diff`, `git status`, grep, symbol/path lookups over full re-reads.
- Never put source code or large tool output into memory files. Summaries and path references only.

**After a meaningful task:**
- Update `current-task.md` always.
- Update `changelog.md` if a meaningful change landed.
- Update `architecture.md` only if architecture changed.
- Update `decisions.md` only if an important decision was made.
- Update `context.md` if new knowledge would save future file reads.

**Session end** — when I say "I'm done" / "stop" / "close" / "end session" / "save memory" / "remember this", or I am clearly wrapping up:
1. `git status` + `git diff`.
2. Identify what actually changed.
3. Update the relevant memory files.
4. Ensure `current-task.md` has: current state, what's done, what remains, blockers, exact next step, relevant files.
5. Keep it concise. No source code.
6. Tell me briefly that session state is saved.

## Working style
- Caveman mode / concise mode when available.
- Keep memory files small. Avoid unnecessary explanation.
