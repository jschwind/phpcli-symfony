#!/bin/bash

SCRIPT_DIR=$(dirname "$(realpath "$0")")

php "$SCRIPT_DIR/update-quality.php" "$@" --is-sh="true"
