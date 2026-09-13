# Digital Library deployment notes

This project is a PHP + SQLite application and is not a static site. It uses PHP sessions, a writable SQLite database in the `data/` folder, and local file uploads for books and cover images.

## Recommended hosting

Use a PHP-capable host such as Render or Railway. GitHub Pages is not suitable because this app needs server-side PHP execution and a writable SQLite database.

## Production configuration

- PHP 8.2+
- `pdo_sqlite` extension enabled
- writable `/var/www/html/data` directory for the SQLite database
- file uploads and generated PDF/book covers served from the app's `books/` folder

## Deploying on Render

1. Push this repository to GitHub.
2. Sign in to Render.
3. Click New + > Web Service.
4. Connect the GitHub repository.
5. Use the included `Dockerfile` and `render.yaml`.
6. Choose the free plan or a paid plan if you want better persistence and stability.
7. Render will build and publish the site automatically.
8. After deployment, visit the URL Render provides.

## Important note about SQLite

SQLite works for demo or small deployments, but the database lives on the local filesystem. If a web host recreates the container or does a redeploy, the database may reset unless the host keeps a persistent disk or you move the app to a managed database.

## Custom domain

After the site is live:

1. In Render, open the service settings.
2. Go to Custom Domains.
3. Add your domain and follow the DNS instructions.
4. Update the DNS records at your registrar.

## Updating later

Push new commits to the connected GitHub repository. Render will rebuild and redeploy automatically if autoDeploy is enabled.

## Local verification

The app was verified locally via PHP's built-in server and returned `HTTP/1.1 200 OK` on `/`.
