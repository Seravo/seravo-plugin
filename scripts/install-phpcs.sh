#!/bin/bash
##
# A script for installing PHPCS and necessary sniffs
##

# Stop on errors
set -e

# Configuration for directories and versions
PHPCS_DIR="${PHPCS_DIR:-"$HOME/.local/bin"}"
PHPCS_VERSION="${PHPCS_VERSION:-"3.3.2"}"
WP_SNIFFS_DIR="${WP_SNIFFS_DIR:-"$HOME/.local/phpcs-sniffs/wpcs"}"
WP_SNIFFS_VERSION="${WP_SNIFFS_VERSION:-"2.1.0"}"
SECURITY_SNIFFS_DIR="${SECURITY_SNIFFS_DIR:-"$HOME/.local/phpcs-sniffs/security"}"
SECURITY_SNIFFS_VERSION="${SECURITY_SNIFFS_VERSION:-"2.0.1"}"
PHP_COMPAT_SNIFFS_DIR="${PHP_COMPAT_SNIFFS_DIR:-"$HOME/.local/phpcs-sniffs/php-compatibility"}"
PHP_COMPAT_SNIFFS_VERSION="${PHP_COMPAT_SNIFFS_VERSION:-"9.3.5"}"

# Install PHPCS
if [ ! -f "${PHPCS_DIR}/phpcs" ] || [ "$1" = "-f" ]
then
  echo "--> Installing PHPCS..."
  mkdir -p "${PHPCS_DIR}"
  curl -sSL "https://github.com/squizlabs/PHP_CodeSniffer/releases/download/${PHPCS_VERSION}/phpcs.phar" -o "${PHPCS_DIR}/phpcs"
  curl -sSL "https://github.com/squizlabs/PHP_CodeSniffer/releases/download/${PHPCS_VERSION}/phpcbf.phar" -o "${PHPCS_DIR}/phpcbf"
  chmod +x "${PHPCS_DIR}/phpcs" "${PHPCS_DIR}/phpcbf"
else
  echo "--> Binary '${PHPCS_DIR}/phpcs' already exists, aborting installation. If you wish to reinstall, please run the command again with the '-f' option..."
fi

# Install WordPress Coding Standards
if [[ ! -d "${WP_SNIFFS_DIR}" || "$1" == "-f" ]]; then
  echo "--> Installing WordPress coding standards..."
  mkdir -p "${WP_SNIFFS_DIR}"
  wget -q "https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/${WP_SNIFFS_VERSION}.tar.gz" \
       -O "${WP_SNIFFS_DIR}/${WP_SNIFFS_VERSION}.tar.gz"

  tar -xf "${WP_SNIFFS_DIR}/${WP_SNIFFS_VERSION}.tar.gz" \
      -C "${WP_SNIFFS_DIR}" \
      --strip-components=1 \
    && rm "${WP_SNIFFS_DIR}/${WP_SNIFFS_VERSION}.tar.gz"
else
  echo "--> Directory '${WP_SNIFFS_DIR}' already exists, aborting installation. If you wish to reinstall, please run the command again with the '-f' option..."
fi

# Install the security standards
if [[ ! -d "${SECURITY_SNIFFS_DIR}" || "$1" == "-f" ]]; then
  echo "--> Installing security sniffs..."
  mkdir -p "${SECURITY_SNIFFS_DIR}"
  wget -q "https://github.com/FloeDesignTechnologies/phpcs-security-audit/archive/${SECURITY_SNIFFS_VERSION}.tar.gz" \
       -O "${SECURITY_SNIFFS_DIR}/${SECURITY_SNIFFS_VERSION}.tar.gz"

  tar -xf "${SECURITY_SNIFFS_DIR}/${SECURITY_SNIFFS_VERSION}.tar.gz" \
      -C "${SECURITY_SNIFFS_DIR}" \
      --strip-components=1 \
    && rm "${SECURITY_SNIFFS_DIR}/${SECURITY_SNIFFS_VERSION}.tar.gz"
else
  echo "--> Directory '${SECURITY_SNIFFS_DIR}' already exists, aborting installation. If you wish to reinstall, please run the command again with the '-f' option..."
fi

# Install the PHP compatibility standards
if [[ ! -d "${PHP_COMPAT_SNIFFS_DIR}" || "$1" == "-f" ]]; then
  echo "--> Installing PHP compatibility sniffs..."
  mkdir -p "${PHP_COMPAT_SNIFFS_DIR}"
  wget -q "https://github.com/PHPCompatibility/PHPCompatibility/archive/${PHP_COMPAT_SNIFFS_VERSION}.tar.gz" \
       -O "${PHP_COMPAT_SNIFFS_DIR}/${PHP_COMPAT_SNIFFS_VERSION}.tar.gz"

  tar -xf "${PHP_COMPAT_SNIFFS_DIR}/${PHP_COMPAT_SNIFFS_VERSION}.tar.gz" \
      -C "${PHP_COMPAT_SNIFFS_DIR}" \
      --strip-components=1 \
    && rm "${PHP_COMPAT_SNIFFS_DIR}/${PHP_COMPAT_SNIFFS_VERSION}.tar.gz"
else
  echo "--> Directory '${PHP_COMPAT_SNIFFS_DIR}' already exists, aborting installation. If you wish to reinstall, please run the command again with the '-f' option..."
fi

# Activate sniffs
echo "--> Activating installed sniffs..."
"${PHPCS_DIR}/phpcs" --config-set installed_paths "${WP_SNIFFS_DIR},${SECURITY_SNIFFS_DIR},${PHP_COMPAT_SNIFFS_DIR}"
if command -v phpenv; then
  phpenv rehash
fi

# Show installed sniffs
"${PHPCS_DIR}/phpcs" -i

echo "Success: PHPCS has been installed to ${PHPCS_DIR}."
