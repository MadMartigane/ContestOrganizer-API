# ContestOrganizer-API
API for the ContestOrganizer project

## Server Configuration

### Set the right user for php-fpm (likely the same as the webserver one)

```bash
user = www-data
group = www-data
```

```sudo systemctl restart nginx```
```sudo systemctl restart php-fpm.service```

## Development & Deployment

### Scripts

- `npm run watch`: Monitors `src/` for changes, runs syntax checks, and syncs to the local server.
- `npm run pre-prod`: Deploys the API to the pre-production environment using the deployment script.
- `npm run prod`: Deploys the API to the production environment using the deployment script. It automatically backups and restores the `data/` directory.

### Deployment

The deployment scripts use `tools/deploy.sh` for robust deployment. Note that `sudo` might be required for permission changes on the target directories.

## Project Structure

- `src/index.php`: Entry point and router.
- `src/controllers/`: Business logic and request handling.
- `src/models/`: Data access and database interactions.
- `src/utils/`: Helper functions and common utilities.
- `src/config/`: Database and application configuration.

