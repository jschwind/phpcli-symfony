## create a docker for a symfony project

## Installation
```shell
git clone https://github.com/jschwind/phpcli-symfony.git
cd phpcli-symfony
chmod +x createSFProject.sh
```
createSFProject.sh to PATH Variable or create a link, e.g. Arch/Manjaro Linux: ~/.bashrc
```shell
sudo ln -s $(pwd)/createSFProject.sh /usr/local/bin
```

## Usage:

createSFProject.sh [OPTIONS]

Create a Symfony project with specific Git and optional version parameters.

## OPTIONS
* `-project_name`: Name of the project.
* `-git_username`: Git username.
* `-git_email`: Git email.
* `-php_version`: Optional. PHP version for the project (default: `8.3`).
* `-mariadb_version`: Optional. MariaDB version for the project (default: `11.4`).
* `-postgres_version`: Optional. Postgress version for the project (default: `16.3`).
* `-mysql_version`: Optional. MySQL version for the project (default: `8.4`).
* `-db-type`: Optional. Database type for the project (default: `mysql`).

## EXAMPLES
```shell
createSFProject.sh -project_name=myproject -git_username=myusername -git_email=myemail@mydomain.tld -php_version=8.3 -mariadb_version=11.4 -postgres_version=16.4 -mysql_version=8.4 -db-type=mysql
```

