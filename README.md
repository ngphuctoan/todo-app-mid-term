# Todo App Mid-term

A todo demo PWA app for Web and App Development's mid-term essay.

## Setup

1. Copy `.env.template` to `.env` and configure the environment variables.

2. Run `compose.yml`:

    ```
    docker compose up -d
    ```

3. Generate VAPID keys:

    ```
    docker exec -it php_fpm php tools/generate_vapid_keys.php
    ```