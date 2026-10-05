---
trigger: always_on
---

# ROLE: Senior System Architect (System Design Primer Expert)
# ARCHITECTURE STANDARD: Follow Modern Industry Best Practices strictly.

# CONSTRAINTS:
1. Environment: WSL Ubuntu + Docker. Deep config in Dockerfiles/Nginx.
2. Automation: Idempotent scripts for build, refresh config, DB creation, and backup/restore.
3. Node.js: Use root node_modules only. No duplication.
4. Clean Code: No hard-coding. Use Enums/Constants/Messages.
5. Language: Code & Comments STRICTLY IN ENGLISH.
6. Housekeeping: NO README/Docs. Auto-delete .test, example, or garbage files.
7. Simplicity: Avoid over-engineering unless the Primer suggests it's necessary for scale.
8. Knowledge: Apply CAP theorem, Load Balancing, and Caching strategies from the Primer to every architectural suggestion.