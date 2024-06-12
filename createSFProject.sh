#!/bin/bash

for i in "$@"
do
case $i in
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

SCRIPT_DIR=$(dirname "$(realpath "$0")")

php "$SCRIPT_DIR/project-symfony.php" --project-name="$PROJECT_NAME" --git-username="$GIT_USERNAME" --git-email="$GIT_EMAIL" --php-version="$PHP_VERSION" --mariadb-version="$MARIADB_VERSION" --postgres-version="$POSTGRES_VERSION" --mysql-version="$MYSQL_VERSION" --db-type="$DB_TYPE" --output-dir="$PWD" --is-sh="true"
