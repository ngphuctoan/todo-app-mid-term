# Todo App Mid-term

A todo Progressive Web App (PWA) developed as part of Web Development and Applications' mid-term essay.

## Setup instructions

To get the project up and running, follow these steps:

### 1. Configure environment variables

Copy `.env.template` file to `.env`:

```bash
cp .env.template .env
```

Then, update the `.env` file with the following environment variables:

- `DB_ROOT_PASS`: MySQL root password.

- `DB_USER`: MySQL username.

- `DB_PASS`: MySQL user password.

- `JWT_SECRET_KEY`: Secret key used for JWT authentication.

### 2. Start the Docker containers

Spin up the Docker containers with the `docker-compose.yml` file:

```bash
docker compose up -d
```

### 3. Enable push notifications

To enable push notifications, first generate a public and private VAPID keys using this command:

```bash
docker compose exec -it php_fpm php tools/generate_vapid_keys.php
```

Once the keys are generated, add them to the `.env` file under the following variables:
  
- `VAPID_PUBLIC_KEY`: The public VAPID key.

- `VAPID_PRIVATE_KEY`: The private VAPID key.

### 4. Launch the app

Ensure that the containers are built and running, then access the app via `localhost:8080`.

## Credits

For a list of credits and acknowledgements, refer to [CREDITS.md](CREDITS.md).