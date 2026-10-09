# prod-like values (ADR-0011 §Environments). No secrets here: they are generated (P4-05) and move to
# SOPS + age in P4-09.
app_env    = "production"
proxy_port = 9443
# HTTPS and the prod-like.sm.localhost name come with P4-08
app_url = "http://localhost:9443"
# The docs app has no production build yet (no standalone output, P4-03)
docs_enabled = false
