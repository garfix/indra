# Tiers

Code should separate these tiers:

- setup: create tables (once)
- definition: type creation and management (setup and upgrade)
- application: php classes, my module (main code)
- background process: clean up snapshots (cron-job)
