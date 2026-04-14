# PHPCLI-Symfony

Create a Docker environment for a Symfony project with integrated code quality tools.

## Prerequisites

- PHP >= 8.2
- Docker & Docker Compose
- Git

## Installation

```shell
git clone https://github.com/jschwind/phpcli-symfony.git
cd phpcli-symfony
chmod +x createSFProject.sh updateSFProject.sh
```

Add the scripts to your PATH or create symlinks, e.g., on Linux:

```shell
sudo ln -s $(pwd)/createSFProject.sh /usr/local/bin/createSFProject
sudo ln -s $(pwd)/updateSFProject.sh /usr/local/bin/updateSFProject
```

## Usage

### createSFProject (Initial Setup)

You can run the script in **Interactive Mode** (recommended) or with **Command Line Options**.

#### Interactive Mode

Simply run the command without any arguments:

```shell
createSFProject
```

The script will guide you through the setup, asking for:
- Project Name (default: current directory name)
- Git Username (default: from git config)
- Git Email (default: from git config)
- PHP Version (default: `8.3`)
- Symfony Version (default: `7.*`)
- Database Type (`mysql`, `postgres`, `mariadb`, `firebird`, `none`, default: `postgres`)
- Code Quality Tools (`ecs`, `rector`, `phpstan`, `phpunit`)
- Output Directory (default: current directory)

#### Command Line Options

```shell
createSFProject [OPTIONS]
```

#### Available Options
* `-project-name`: Name of the project.
* `-git-username`: Git username.
* `-git-email`: Git email.
* `-php-version`: PHP version (default: `8.3`).
* `-symfony-version`: Symfony version (default: `7.*`).
* `-db-type`: Database type (`mysql`, `postgres`, `mariadb`, `firebird`, `none`) (default: `postgres`).
* `-mariadb-version`: MariaDB version (default: `11.5`).
* `-postgres-version`: PostgreSQL version (default: `17.0`).
* `-mysql-version`: MySQL version (default: `9.1`).
* `-firebird-version`: Firebird version (default: `5.0`).
* `-code-quality`: Enable code quality tools (`true`/`false`).
* `-tools`: Comma-separated list of tools (`ecs,rector,phpstan,phpunit`).
* `-output-dir`: Target directory for project generation.

### updateSFProject (Update Code Quality Tools)

If you have an existing project and only want to add or update the code quality tools, use `updateSFProject`.

#### Interactive Mode

```shell
updateSFProject
```

The script will ask for:
- Project Directory (defaults to current directory)
- PHP Version (automatically detected from `composer.json` if available)
- Symfony Version (automatically detected from `composer.json` if available)
- Tools to add

#### Command Line Options

```shell
updateSFProject -php-version=8.4 -symfony-version=7.2 -tools=ecs,rector -output-dir=.
```

## Generated Project Structure

After running `createSFProject`, you will find a `docker/` directory in your project containing:

- `bash.sh` / `bash.bat`: Enter the PHP container as the `application` user.
- `root.sh` / `root.bat`: Enter the PHP container as the `root` user.
- `docker-compose.yml`: Docker configuration.
- `web/Dockerfile`: PHP environment configuration.

## Code Quality Integration

If enabled, the tool sets up a modern development environment using `bamarni/composer-bin-plugin`:

- **ECS (Easy Coding Standard)**: Coding style checks.
- **Rector**: Automated code upgrades and refactoring.
- **PHPStan**: Static analysis.
- **PHPUnit**: Unit and functional testing.

All tools are installed in the `vendor-bin/` directory to avoid dependency conflicts with your main project. Composer scripts like `composer ci`, `composer test`, and `composer bin-rector` are automatically added and configured.

## Examples

**Interactive setup:**
```shell
createSFProject
```

**Non-interactive with specific tools:**
```shell
createSFProject -project-name=my-app -php-version=8.4 -db-type=mariadb -code-quality=true -tools=ecs,phpstan,phpunit
```

