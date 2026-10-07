## What

<!-- One or two sentences. Link the backlog ID / issue: "P2-03, closes #12" -->

## Why

<!-- The problem this solves. Link REQ / RFC / ADR / PRB if any. -->

## How tested

<!-- Commands run (`make verify`), new tests, manual checks. Screenshots for UI. -->

## Risks and rollback

<!-- What could break, how to undo (revert, migrate:rollback, feature flag). "None" is fine for small changes. -->

## Release note

<!-- One line for docs/releases, or "none". -->

## Checklist

- [ ] One purpose; < 400 changed lines (excluding generated files) or explained
- [ ] Tests at the right level; `make verify` green
- [ ] API change → `make openapi` run, FE types and docs site updated
- [ ] Migration reversible or expand/contract
- [ ] Docs / runbooks updated where behaviour or operations changed
- [ ] No secrets, personal data or AI attribution trailers
