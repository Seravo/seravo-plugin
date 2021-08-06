#!/bin/bash
##
# A script for comparing available Rector rules and Rector rules used by us.
# Rector's development pace is quite fast so there are changes constantly.
# This script makes keeping up easier. Make sure to add purposely disabled
# rules at the bottom of Rector.php.
##

RECTOR_RULES_UPSTREAM="https://raw.githubusercontent.com/rectorphp/rector/main/docs/rector_rules_overview.md"

curl "${RECTOR_RULES_UPSTREAM}" -s | awk '/^- class:/' | sed -n "s/.*\`\(.*\)\`.*/\1/p" | sort > upstream-rules.txt
grep 'services->set' rector.php | sed -n 's/.*(\\\(.*\)::.*/\1/p' | sort | uniq > current-rules.txt

echo
echo "Rules added upstream:"
echo
comm -13 current-rules.txt upstream-rules.txt

echo
echo "Rules removed upstream:"
echo
comm -23 current-rules.txt upstream-rules.txt

rm upstream-rules.txt current-rules.txt
