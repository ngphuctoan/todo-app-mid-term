# Todo App Mid-term

A todo demo PWA app for Web and App Development's mid-term essay.

## Setup

1. Copy `.env.template` to `.env` and configure the environment variables:

    - `DB_ROOT_PASS`: MySQL root password.
    
    - `DB_USER`: MySQL username.

    - `DB_PASS`: MySQL user password.

    - `JWT_SECRET_KEY`: JWT secret key.

2. Run `compose.yml`:

    ```
    docker compose up -d
    ```

3. Generate VAPID keys and add them to `.env`:

    ```
    docker compose exec -it php_fpm php tools/generate_vapid_keys.php
    ```

## Credits

Refer to [CREDITS.md](CREDITS.md).