#!/bin/bash
##
# A script for installing PHPCS and necessary sniffs
##

# Configuration for directories and versions
PHPCS_DIR="${PHPCS_DIR:-"$HOME/phpcs/phpcs"}"
PHPCS_VERSION="${PHPCS_VERSION:-"3.3.2"}"
WP_SNIFFS_DIR="${WP_SNIFFS_DIR:-"$HOME/phpcs/sniffs/wpcs"}"
WP_SNIFFS_VERSION="${WP_SNIFFS_VERSION:-"2.1.0"}"
SECURITY_SNIFFS_DIR="${SECURITY_SNIFFS_DIR:-"$HOME/phpcs/sniffs/security"}"
SECURITY_SNIFFS_VERSION="${SECURITY_SNIFFS_VERSION:-"2.0.0"}"
PHP_COMPAT_SNIFFS_DIR="${PHP_COMPAT_SNIFFS_DIR:-"$HOME/phpcs/sniffs/php-compatibility"}"
PHP_COMPAT_SNIFFS_VERSION="${PHP_COMPAT_SNIFFS_VERSION:-"9.1.1"}"

# Install PHPCS
if [[ ! -d "${PHPCS_DIR}" || "$1" == "-f" ]]; then
  echo "--> Installing PHPCS..."
  mkdir -p "${PHPCS_DIR}"
  wget -q "https://github.com/squizlabs/PHP_CodeSniffer/archive/${PHPCS_VERSION}.tar.gz" \
      -O "${PHPCS_DIR}/${PHPCS_VERSION}.tar.gz"

  # Install PHPCS directly into target dir without the additional PHP_CodeSniffer-... directory
  tar -xf "${PHPCS_DIR}/${PHPCS_VERSION}.tar.gz" \
      -C "${PHPCS_DIR}" \
      --strip-components=1 \
    && rm "${PHPCS_DIR}/${PHPCS_VERSION}.tar.gz"
else
  echo "--> Directory '${PHPCS_DIR}' already exists, aborting installation. If you wish to reinstall, please run the command again with the '-f' option..."
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
"${PHPCS_DIR}/bin/phpcs" --config-set installed_paths "${WP_SNIFFS_DIR},${SECURITY_SNIFFS_DIR},${PHP_COMPAT_SNIFFS_DIR}"
if $(command -v phpenv); then
  phpenv rehash
fi

# Show installed sniffs
"${PHPCS_DIR}/bin/phpcs" -i

echo "Success: PHPCS has been installed to ${PHPCS_DIR}."

# Suggest to add to PATH if it does not exist there
if [[ ":${PATH}:" != *":${PHPCS_DIR}/bin:"* ]]; then
  echo ""
  echo "NOTICE: To run 'phpcs' on your machine, please add the line 'export PATH=\"${PHPCS_DIR}/bin:\$PATH\"' to your ~/.bashrc file.'"
fi
