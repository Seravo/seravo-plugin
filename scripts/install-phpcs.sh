#!/bin/bash
##
# A script for installing PHPCS and necessary sniffs
##

# Stop on errors
set -e

# Configuration for directories and versions
PHPCS_DIR="${PHPCS_DIR:-"$HOME/.local/php/CodeSniffer"}"
BIN_DIR="${BIN_DIR:-"$HOME/.local/bin"}"

mkdir -p "${PHPCS_DIR}" "${BIN_DIR}"
curl -fsSL https://github.com/squizlabs/PHP_CodeSniffer/archive/3.5.5.tar.gz | tar xz --strip 1 -C "${PHPCS_DIR}"
ln -sf "${PHPCS_DIR}/bin/phpcs" "${BIN_DIR}/phpcs"
ln -sf "${PHPCS_DIR}/bin/phpcbf" "${BIN_DIR}/phpcbf"
curl -fsSL https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/master.tar.gz | tar xz --strip 1 -C "${PHPCS_DIR}/src/Standards"
curl -fsSL https://github.com/FloeDesignTechnologies/phpcs-security-audit/archive/master.tar.gz | tar xz --strip 1 -C "${PHPCS_DIR}/src/Standards"
curl -fsSL https://github.com/PHPCompatibility/PHPCompatibility/archive/master.tar.gz | tar xz --strip 1 -C "${PHPCS_DIR}/src/Standards"
# Clean up cruft files left behind from unpacking tar packages
find "${PHPCS_DIR}/src/Standards" -maxdepth 1 -type f -delete
curl -fsSL https://raw.githubusercontent.com/PHPCompatibility/PHPCompatibility/master/PHPCSAliases.php -o "${PHPCS_DIR}/src/Standards/PHPCSAliases.php"

# Show installed sniffs
"${BIN_DIR}/phpcs" -i

echo "Success: PHPCS has been installed to ${PHPCS_DIR}."
