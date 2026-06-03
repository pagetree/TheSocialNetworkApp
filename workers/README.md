# Workers

CLI scripts for background jobs. Run from the **project root**, not from this folder alone.

```bash
php workers/compute-post-scores.php
```

On Railway: deploy the worker service from the repo root (no root directory override). Use `railway.worker.json` at the project root.
