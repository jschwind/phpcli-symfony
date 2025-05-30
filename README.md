# PHPCLI-Symfony

Create a Docker environment for a Symfony project.

## Installation

```shell
git clone https://github.com/jschwind/phpcli-symfony.git
cd phpcli-symfony
chmod +x createSFProject.sh
```

Add `createSFProject.sh` to your PATH or create a symlink, e.g., on Arch/Manjaro Linux via `~/.bashrc`:

```shell
sudo ln -s $(pwd)/createSFProject.sh /usr/local/bin/createSFProject
```

## Usage

```shell
createSFProject [OPTIONS]
```

Create a Symfony project with specific Git and optional version parameters.

### Options
* `-project-name`: Name of the project.
* `-git-username`: Git username.
* `-git-email`: Git email.
* `-php-version`: Optional. PHP version for the project (default: `8.3`).
* `-mariadb-version`: Optional. MariaDB version for the project (default: `11.5`).
* `-postgres-version`: Optional. Postgress version for the project (default: `17.0`).
* `-mysql-version`: Optional. MySQL version for the project (default: `9.1`).
* `-firebird-version`: Optional. Firebird version for the project (default: `5.0`).
* `-db-type`: Optional. Database type for the project (default: `mariadb`).
* `-symfony-version`: Optional. Symfony version for the project (default: `7`).

## Examples

```shell
createSFProject -project-name=myproject -git-username=myusername -git-email=myemail@mydomain.tld -php-version=8.3 -mariadb-version=11.6 -postgres-version=17.0 -mysql-version=9.1 -firebird-version=5.0 -db-type=mariadb -symfony-version=7
```

