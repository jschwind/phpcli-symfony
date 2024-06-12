#!/bin/bash

show_help() {
cat << EOF
Usage: ${0##*/} [OPTIONS]

Create a Symfony project with specific Git and optional version parameters.

OPTIONS
    -project_name     Name of the project.
    -git_username     Git username.
    -git_email        Git email.
    -php_version      Optional. PHP version for the project (default: 8.3).
    -mariadb_version  Optional. MariaDB version for the project (default: 11.4).
    -postgres_version Optional. Postgress version for the project (default: 16.3).
    -mysql_version    Optional. MySQL version for the project (default: 8.4).
    -db-type          Optional. Database type for the project (default: mysql).

EXAMPLES
    ${0##*/} -project_name=myproject -git_username=myusername -git_email=myemail@mydomain.tld -php_version=8.3 -mariadb_version=11.4 -postgres_version=16.4 -mysql_version=8.4 -db-type=mysql
EOF
}

for i in "$@"
do
case $i in
    -help)
    show_help
    exit 0
    ;;
    --help)
      show_help
      exit 0
      ;;
    -project_name=*)
    PROJECT_NAME="${i#*=}"
    shift
    ;;
    -git_username=*)
    GIT_USERNAME="${i#*=}"
    shift
    ;;
    -git_email=*)
    GIT_EMAIL="${i#*=}"
    shift
    ;;
    -php_version=*)
    PHP_VERSION="${i#*=}"
    shift
    ;;
    -mariadb_version=*)
    MARIADB_VERSION="${i#*=}"
    shift
    ;;
    -postgres_version=*)
    POSTGRES_VERSION="${i#*=}"
    shift
    ;;
    -mysql_version=*)
    MYSQL_VERSION="${i#*=}"
    shift
    ;;
    -db-type=*)
    DB_TYPE="${i#*=}"
    shift
    ;;
    *)
    ;;
esac
done


if [ -z "$PROJECT_NAME" ] || [ -z "$GIT_USERNAME" ] || [ -z "$GIT_EMAIL" ]; then
    echo "Error: Missing required parameters."
    show_help
    exit 1
fi

PHP_VERSION=${PHP_VERSION:-$(php -v | grep -oP '^PHP \K[^\s]+')}
MARIADB_VERSION=${MARIADB_VERSION:-"system default"}

SCRIPT_DIR=$(dirname "$(realpath "$0")")

php "$SCRIPT_DIR/project-symfony.php" --project-name="$options['project-name']" --git-username="$options['git-username']" --git-email="$options['git-email']" --php-version="$options['php-version']" --mariadb_version="$options['mariadb-version']" --postgres-version="$options['postgres-version']" --mysql-version="$options['mysql-version']" --db-type="$options['db-type']" --output-dir="$options['output-dir']"
