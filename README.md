[![phpunit](https://github.com/danilovl/web-command-bundle/actions/workflows/phpunit.yml/badge.svg)](https://github.com/danilovl/web-command-bundle/actions/workflows/phpunit.yml)
[![downloads](https://img.shields.io/packagist/dt/danilovl/web-command-bundle)](https://packagist.org/packages/danilovl/web-command-bundle)
[![latest Stable Version](https://img.shields.io/packagist/v/danilovl/web-command-bundle)](https://packagist.org/packages/danilovl/web-command-bundle)
[![license](https://img.shields.io/packagist/l/danilovl/web-command-bundle)](https://packagist.org/packages/danilovl/web-command-bundle)

# WebCommandBundle

A Symfony bundle for running console commands via web interface (REST API). Supports both synchronous and asynchronous (via Messenger) execution.

## Features

- REST API for listing and running commands.
- Support for asynchronous command execution using Symfony Messenger.
- Configurable API prefix, console path, memory and time limits.
- Entity-based management for commands, jobs and history.
- EasyAdmin integration for managing commands and viewing history.
- Flexible command configuration (voters, async, history, parameters, etc.).
- Automatic PHP binary detection.

## Available dashboard

![Alt text](/readme/dashboard.png?raw=true "Dashboard")

## Installation

1. Install the bundle via composer:

```bash
composer require danilovl/web-command-bundle
```

2. Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Danilovl\WebCommandBundle\WebCommandBundle::class => ['all' => true],
];
```

3. Import routes in `config/routing.yaml` (routing is not automatic):

```yaml
web_command:
    resource: '@WebCommandBundle/src/Resources/config/routing.yaml'
```

4. Doctrine entity mapping

Add entity mapping to `doctrine.yaml` in `packages` folder.

```yaml
orm:
  mappings:
    Danilovl\WebCommandBundle:
      is_bundle: false
      type: attribute
      dir: "%kernel.project_dir%/vendor/danilovl/web-command-bundle/src/Entity/"
      prefix: 'Danilovl\WebCommandBundle\Entity'
```

5. Update your database schema:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Configuration

Create `config/packages/web_command.yaml`:

```yaml
danilovl_web_command:
    api_prefix: '/danilovl/web-command/api'
    console_path: null
    enable_async: false
    default_timeout: null
    default_time_limit: null
    default_memory_limit: null
    enabled_admin_dashboard: false
    enabled_dashboard_live_status: true
```

*Note:*
- `console_path`: if `null` (default), it's `%kernel.project_dir%/bin/console`. If set, it's used as is (absolute path).
- `default_timeout`, `default_time_limit` and `default_memory_limit` are `null` by default.
- `default_timeout`: default timeout (seconds) for synchronous command execution via API.
- `default_time_limit`: default timeout (seconds) for asynchronous command execution.
- `default_memory_limit`: applied via `-d memory_limit=...` during process execution.
- `enabled_admin_dashboard`: enable EasyAdmin dashboard integration.
- `enabled_dashboard_live_status`: enable/disable interval requests to check job status on the web dashboard.

### Messenger Configuration

To enable asynchronous execution, configure Symfony Messenger:

```yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            'Danilovl\WebCommandBundle\Message\RunCommandMessage': async
```

## Usage

### REST API

The bundle provides a REST API to interact with commands. By default, the API is available under the `/danilovl/web-command/api` prefix.

### Admin Interface

The bundle includes a built-in EasyAdmin dashboard for managing commands and viewing execution history.
To use it, ensure you have `EasyAdminBundle` installed and configured, and enable it in the configuration:

```yaml
danilovl_web_command:
    enabled_admin_dashboard: true
```

The dashboard is available at `/admin/danilovl/web-command`.

The admin interface allows you to:
- Create, update, and delete commands.
- View execution history and command outputs.

### Web Dashboard

The bundle also provides a standalone web dashboard to run commands and view their history.

The dashboard is available at `/danilovl/web-command/dashboard`.
**Route Name:** `danilovl_web_command_dashboard_index`

**Features:**
- List of active commands.
- Input for additional parameters.
- Formatted output display.
- History of recent executions with pagination.

To use the web dashboard, you need to install assets:
```bash
php bin/console assets:install
```

#### Managing Commands

Each command is represented as an entity with several configuration options:

- **Name**: The display name of the command.
- **Command**: The actual Symfony console command (e.g., `app:sync-users`).
- **Parameters**: Default arguments and options (e.g., `["--force", "prod"]`).
- **Allow Custom Parameters**: If enabled, users can provide additional parameters when running the command.
- **Async**: Enable asynchronous execution via Symfony Messenger.
- **Save History**: Whether to record execution history in the database.
- **Save Output**: Save the command's stdout/stderr in the history record.
- **Voter Class**: Specify a Symfony Voter class or a security role (e.g., `ROLE_ADMIN`) to restrict command execution based on custom logic.
- **Active**: Easily enable/disable command from being executed.
- **Description**: Add notes about the command's purpose.

When adding a command, specify its name and optional default parameters.
The `Parameters` field must be a **JSON array of strings**. These are arguments and options passed to the command.

**Example for `app:example --fix`:**
- **Name**: `app:example`
- **Parameters**: `["--fix"]`

**Example for `app:example arg1 --option=value`:**
- **Name**: `app:example`
- **Parameters**: `["arg1", "--option=value"]`

#### 1. List Commands
Retrieve a list of all active commands that can be executed via the web interface.

**Endpoint:** `GET /danilovl/web-command/api/commands/`  
**Route Name:** `danilovl_web_commands`

**Response (200 OK):**
```json
[
  {
    "id": 1,
    "name": "app:example-command",
    "command": "app:example",
    "allowCustomParameters": true,
    "voterClass": "App\\Security\\Voter\\ExampleVoter",
    "description": "This is an example command description",
    "active": true,
    "async": false,
    "saveHistory": true,
    "saveOutput": false,
    "parameters": [],
    "createdAt": "2024-03-28T10:00:00+00:00",
    "updatedAt": null
  }
]
```

#### 2. Run Command
Execute a command.

**Endpoint:** `POST /danilovl/web-command/api/commands/{id}/run`  
**Route Name:** `danilovl_web_command_run`

**Request Body:**
```json
{
  "input": ["--option=value", "argument"],
  "timeout": 300
}
```

**Response (200 OK - Synchronous execution):**
```json
{
  "output": "Command output string...",
  "exitCode": 0,
  "duration": 1.5
}
```

**Response (202 Accepted - Asynchronous execution):**
*Returned when the command has `async: true` and asynchronous execution is enabled.*
```json
{
  "status": "queued",
  "jobId": 1
}
```

**Response (404 Not Found):**
```json
{
  "error": "Command not found or disabled"
}
```

#### 3. List Histories
Retrieve a paginated list of histories for a specific command.

**Endpoint:** `GET /danilovl/web-command/api/commands/{command}/histories`  
**Route Name:** `danilovl_web_command_histories`

**Path Parameters:**
- `command`: The ID of the command.

**Query Parameters:**
- `page` (optional): Page number (default: 1)

**Response (200 OK):**
```json
{
  "histories": [
    {
      "id": 1,
      "command": { "id": 1, "name": "app:example-command", ... },
      "async": true,
      "duration": 1.5,
      "exitCode": 0,
      "errorMessage": null,
      "output": "Command output...",
      "metaInfo": { "userId": 1, "userName": "admin" },
      "createdAt": "2024-03-28 10:00:00"
    }
  ],
  "totalPages": 1,
  "currentPage": 1
}
```

#### 4. Get History Details
Retrieve details and output of a specific history record.

**Endpoint:** `GET /danilovl/web-command/api/commands/histories/{id}`  
**Route Name:** `danilovl_web_command_history_detail`

**Response (200 OK):**
```json
{
  "id": 1,
  "command": { "id": 1, "name": "app:example-command", ... },
  "async": true,
  "duration": 1.5,
  "exitCode": 0,
  "errorMessage": null,
  "output": "Command output...",
  "metaInfo": { "userId": 1, "userName": "admin" },
  "createdAt": "2024-03-28 10:00:00"
}
```

**Response (404 Not Found):**
```json
{
  "error": "History not found"
}
```

#### 5. Get Job Status
Retrieve the status of a specific job.

**Endpoint:** `GET /danilovl/web-command/api/jobs/{id}/status`  
**Route Name:** `danilovl_web_command_job_status`

**Response (200 OK):**
```json
{
  "status": "queued"
}
```

### Security

#### Voter

Each command can be protected by a Symfony Voter. Specify a **Voter Class** (or a role like `ROLE_ADMIN`) in the command configuration. The bundle will check permissions using `AuthorizationCheckerInterface::isGranted()`.

#### URL Security

To restrict access to the dashboards and API to specific roles, configure Symfony's `access_control` in `config/packages/security.yaml`:

```yaml
security:
    access_control:
        - { path: ^/api/web, roles: ROLE_ADMIN }
        - { path: ^/danilovl/web-command/dashboard, roles: ROLE_ADMIN }
```

## Events

The bundle dispatches events during the command execution lifecycle, allowing you to intercept and modify the process.

### CommandStartEvent

Dispatched before a command is executed.

**Usage:**
- Cancel command execution.
- Modify command input.
- Add or modify meta information.

```php
use Danilovl\WebCommandBundle\Event\CommandStartEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class CommandStartListener
{
    public function __invoke(CommandStartEvent $event): void
    {
        if ($someCondition) {
            $event->setShouldContinue(false);
            $event->setReason('Reason for cancellation');
        }

        $metaInfo = $event->getMetaInfo() ?? [];
        $metaInfo['custom_key'] = 'custom_value';
        
        $event->setMetaInfo($metaInfo);
    }
}
```

### CommandEndEvent

Dispatched after a command has finished execution (successfully or with an error).

```php
use Danilovl\WebCommandBundle\Event\CommandEndEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class CommandEndListener
{
    public function __invoke(CommandEndEvent $event): void
    {
        $exitCode = $event->getExitCode();
        $output = $event->getOutput();
        
        // Do something with the result
    }
}
```

## Meta Information

The `History` entity includes a `metaInfo` field (JSON). By default, if the Symfony Security component is available, it automatically stores:
- `userId`: ID of the current user.
- `userIdentifier`: Identifier of the current user.

You can customize this data via `CommandStartEvent::setMetaInfo()`. If you set it to `null`, no meta information will be saved.

## Database Schema Example

```sql
CREATE TABLE danilovl_web_command
(
    id                      INT AUTO_INCREMENT NOT NULL,
    name                    VARCHAR(255)       NOT NULL,
    command                 VARCHAR(255)       NOT NULL,
    parameters              JSON               NOT NULL,
    allow_custom_parameters TINYINT            NOT NULL,
    voter_class             VARCHAR(255)                DEFAULT NULL,
    active                  TINYINT            NOT NULL,
    async                   TINYINT            NOT NULL,
    save_history            TINYINT(1)         NOT NULL DEFAULT 1,
    save_output             TINYINT(1)         NOT NULL DEFAULT 0,
    description             LONGTEXT                    DEFAULT NULL,
    created_at              DATETIME           NOT NULL,
    updated_at              DATETIME                    DEFAULT NULL,

    UNIQUE INDEX UNIQ_73DBE01B5E237E06 (name),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

CREATE TABLE danilovl_web_command_job
(
    id         INT AUTO_INCREMENT NOT NULL,
    command_id INT                NOT NULL,
    input      JSON               NOT NULL,
    status     VARCHAR(20)        NOT NULL,
    created_at DATETIME           NOT NULL,

    PRIMARY KEY (id),
    INDEX IDX_3389C56E33E1689A (command_id),
    CONSTRAINT FK_3389C56E33E1689A FOREIGN KEY (command_id) REFERENCES danilovl_web_command (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4;

CREATE TABLE danilovl_web_command_history
(
    id            INT AUTO_INCREMENT NOT NULL,
    command_id    INT                NOT NULL,
    async         TINYINT(1)         NOT NULL,
    duration      DOUBLE PRECISION   NOT NULL,
    exit_code     INT                NOT NULL,
    error_message LONGTEXT DEFAULT NULL,
    output        LONGTEXT DEFAULT NULL,
    meta_info     JSON     DEFAULT NULL,
    created_at    DATETIME           NOT NULL,

    PRIMARY KEY (id),
    INDEX IDX_HISTORY_COMMAND (command_id),
    CONSTRAINT FK_HISTORY_COMMAND FOREIGN KEY (command_id) REFERENCES danilovl_web_command (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4;

```

## License

The WebCommandBundle is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
