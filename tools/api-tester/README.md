# API Tester for ContestOrganizer

A standalone HTML/JavaScript utility for testing and exploring the ContestOrganizer API. This tool provides an intuitive graphical interface for making HTTP requests, managing authentication tokens, and visualizing API responses without requiring any build tools or dependencies.

## Features

The API Tester offers a comprehensive set of features designed for efficient API development and testing:

- **JWT Authentication Testing** - Login with credentials to obtain and manage JWT tokens. The tool automatically decodes and displays token payload information including user role, subject, and expiration.
- **User Management** - Administrative users can list all users and create new user accounts with customizable roles (user or admin).
- **File CRUD Operations** - Full Create, Read, Update, and Delete operations for files. List all files, view individual file details, create new files with custom content, update existing file content, and delete files.
- **Custom API Requests** - Send raw HTTP requests with any method (GET, POST, PUT, DELETE), custom URL endpoints, and JSON request bodies. This allows testing any API endpoint beyond the built-in operations.
- **Response Visualization** - View response status codes, headers, and formatted JSON body in a clear, readable format. Response data is syntax-highlighted for easy inspection.
- **Session Management** - Persistent login state using localStorage. View decoded JWT tokens with their claims including subject, role, issued time, and expiration.

## Quick Start

Getting started with the API Tester requires no installation or build steps:

1. Navigate to the `tools/api-tester/` directory in your file system
2. Open `index.html` directly in your web browser
3. The tool is ready to use immediately

No server is required, no Node.js dependencies to install, and no build process to run. Simply open the HTML file and start testing.

## Usage Guide

### Step 1: Configure API URL

In the header section, locate the "API Base URL" input field. Enter the base URL of the ContestOrganizer API you want to test. For local development, this might be `http://localhost/contest/api` or your local server address. In production, this would be your live API domain such as `https://api.example.com`.

### Step 2: Login with Credentials

Enter your email and password in the authentication form and click "Login". Upon successful authentication, the tool will:
- Store access and refresh tokens in localStorage
- Display the raw token values
- Decode and display the JWT payload showing your user ID, role, and token expiration
- Reveal additional features based on your role (admin features for administrators)

### Step 3: Explore Available Endpoints

Once authenticated, the interface reveals options based on your role:
- **All Users**: Can access Files Management and Raw Request features
- **Administrators**: Additionally have access to Users Management for listing and creating users

### Step 4: Make Requests and View Responses

Each section provides buttons and forms for different operations. After making a request:
- The response status code appears at the top of the Response panel
- Response headers are displayed in a formatted block
- Response body shows the JSON response with proper formatting

For custom requests, use the Raw Request section to specify any HTTP method, endpoint, and JSON body.

## API Endpoints Supported

The tool is pre-configured to work with the following ContestOrganizer API endpoints:

### Authentication

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/auth/login` | POST | Authenticate with email and password to obtain JWT tokens |
| `/auth/logout` | POST | Invalidate current session and clear tokens |

**Example Login Request:**
```json
{
  "email": "admin@example.com",
  "password": "your-password"
}
```

### Users Management

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/users/list` | GET | Retrieve a list of all registered users |
| `/users/create` | POST | Create a new user with specified email, password, and role |

**Example Create User Request:**
```json
{
  "email": "newuser@example.com",
  "password": "secure-password",
  "role": "user"
}
```

### Files Management

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/files/list` | GET | Retrieve a list of all files |
| `/files/get/{id}` | GET | Retrieve a specific file by its ID |
| `/files/create` | POST | Create a new file with name and content |
| `/files/update/{id}` | PUT | Update an existing file's content |
| `/files/delete/{id}` | DELETE | Delete a file by its ID |

**Example Create File Request:**
```json
{
  "filename": "example.txt",
  "content": "File content here"
}
```

**Example Update File Request:**
```json
{
  "content": "Updated file content"
}
```

## File Structure

```
tools/api-tester/
├── index.html          # Main HTML file with UI structure
├── styles.css          # Styling for the application
├── app.js              # Main application logic
└── modules/
    ├── jwt.js          # JWT token handling and decoding
    ├── api.js          # API request utilities
    └── ui.js           # UI manipulation and display
```

All files use ES6 modules and are loaded directly in the browser without any build step. The module system allows for clean separation of concerns between authentication logic, API communication, and user interface management.

## Browser Compatibility

The API Tester requires a modern web browser with ES6 module support. The following browsers are fully supported:

- Google Chrome (latest version)
- Mozilla Firefox (latest version)
- Apple Safari (latest version)
- Microsoft Edge (latest version)

Internet Explorer is not supported. The tool relies on modern JavaScript features including ES6 modules, async/await, fetch API, and localStorage.

## Security Notes

When using the API Tester, keep the following security considerations in mind:

- **Token Storage** - Access and refresh tokens are stored in the browser's localStorage. While convenient for session persistence, be aware that localStorage is accessible via JavaScript on the same domain, making it vulnerable to XSS attacks.
- **Logout Behavior** - Always use the "Logout" button when finished testing. This clears tokens from localStorage and ensures your session is properly invalidated.
- **Production Use** - When testing against production APIs, always use HTTPS connections. Never transmit credentials or sensitive data over unencrypted HTTP connections.
- **Token Expiration** - JWT tokens have a limited lifespan. If your token expires during testing, you will need to re-authenticate by logging in again.
- **Sensitive Data** - Avoid testing with real production credentials. Create separate test accounts for development and testing purposes.
- **Browser Storage** - Clearing your browser's cache and localStorage will log you out and require re-authentication.

## Additional Information

For development purposes, you can customize the API base URL to point to different environments (local, staging, production). The Raw Request feature allows testing any endpoint not explicitly covered by the built-in forms, providing maximum flexibility for API exploration.

The tool automatically includes the JWT access token in the Authorization header for all authenticated requests using the Bearer token scheme.
