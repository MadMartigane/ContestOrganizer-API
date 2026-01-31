# ContestOrganizer-API
API for the ContestOrganizer project

## 📋 Overview & Operation
The **ContestOrganizer-API** is a lightweight **PHP 8.x** solution designed for managing sports tournaments. It uses a **simplified MVC architecture** without heavy frameworks, focusing on speed and ease of deployment.

*   **Single Entry Point**: `index.php` centralizes all requests.
*   **Custom Routing**: URLs follow the pattern `index.php/{action}/{subject}/{option}` (e.g., `index.php/list/tournaments`).
*   **Central Manager**: A `CommonController` (Singleton) handles URI parsing and JSON output formatting.
*   **Business Logic**: The `Procedures` class maps requests to specific functions.
*   **Storage**: Currently based on **JSON files** (e.g., `data/tournaments.json`), providing maximum portability without immediate database dependencies.

## 🚀 Features & Capabilities
*   **Tournament Management**: Store (`store`) and retrieve (`list`) tournament data.
*   **Configuration**: Generate configuration templates via `create/config`.
*   **Debug System**: Integrated debug messages within JSON responses to facilitate frontend development.
*   **Basic Security**: Protection against direct file access and URL argument sanitization.

## 🛠️ Quality Assessment
| Criterion | Status | Observations |
| :--- | :--- | :--- |
| **Architecture** | ✅ Good | Modular, clear, and easy to understand structure. |
| **Code Quality** | ✅ Clean | Strict PHP 8 typing, follows basic PSR standards. |
| **Robustness** | ⚠️ Medium | Centralized error handling, but input data validation is still basic. |
| **Scalability** | ⚠️ Limited | Manual routing and file-based storage may become complex at scale. |
| **Implementation** | 🛠️ In Progress | Several models (`models/`) are placeholders or contain dead code. |

## 🎯 Conclusion
This is an **efficient and well-structured** API for small to medium-sized projects. Its strength lies in its **lightweight** nature and lack of complex dependencies. For large-scale production, finalizing the database layer (already started) and adding automated tests would be recommended.

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

