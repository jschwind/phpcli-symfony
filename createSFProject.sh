#!/bin/bash

PROJECT_NAME=""
GIT_USERNAME=""
GIT_EMAIL=""
PHP_VERSION=""
MARIADB_VERSION=""
POSTGRES_VERSION=""
MYSQL_VERSION=""
FIREBIRD_VERSION=""
DB_TYPE=""
SF_VERSION=""

for i in "$@"
do
case $i in
    -project-name=*)
    PROJECT_NAME="${i#*=}"
    shift
    ;;
    -git-username=*)
    GIT_USERNAME="${i#*=}"
    shift
    ;;
    -git-email=*)
    GIT_EMAIL="${i#*=}"
    shift
    ;;
    -php-version=*)
    PHP_VERSION="${i#*=}"
    shift
    ;;
    -mariadb-version=*)
    MARIADB_VERSION="${i#*=}"
    shift
    ;;
    -postgres-version=*)
    POSTGRES_VERSION="${i#*=}"
    shift
    ;;
    -mysql-version=*)
    MYSQL_VERSION="${i#*=}"
    shift
    ;;
    -firebird-version=*)
    FIREBIRD_VERSION="${i#*=}"
    shift
    ;;
    -db-type=*)
    DB_TYPE="${i#*=}"
    shift
    ;;
    -symfony-version=*)
    SF_VERSION="${i#*=}"
    shift
    ;;
    *)
    ;;
esac
done

SCRIPT_DIR=$(dirname "$(realpath "$0")")

php "$SCRIPT_DIR/project-symfony.php" --project-name="$PROJECT_NAME" --git-username="$GIT_USERNAME" --git-email="$GIT_EMAIL" --php-version="$PHP_VERSION" --mariadb-version="$MARIADB_VERSION" --postgres-version="$POSTGRES_VERSION" --mysql-version="$MYSQL_VERSION" --firebird-version="$FIREBIRD_VERSION" --db-type="$DB_TYPE" --symfony-version="$SF_VERSION" --output-dir="$PWD" --is-sh="true"
