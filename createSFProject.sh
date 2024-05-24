#!/bin/bash

show_help() {
cat << EOF
Usage: ${0##*/} [OPTIONS]

Create a Symfony project with specific Git and optional version parameters.

OPTIONS
    -project_name     Name of the project.
    -git_username     Git username.
    -git_email        Git email.
    -php_version      Optional. PHP version for the project (default: system wide version).
    -mariadb_version  Optional. MariaDB version for the project (default: system wide version).

EXAMPLES
    ${0##*/} -project_name=myproject -git_username=myusername -git_email=myemail -php_version=8.2 -mariadb_version=10.4
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
php "$SCRIPT_DIR/project-symfony.php" $PROJECT_NAME $GIT_USERNAME $GIT_EMAIL $PHP_VERSION $MARIADB_VERSION "$PWD"
