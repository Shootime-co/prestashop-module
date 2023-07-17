#!/usr/bin/env bash

VERSION=$1
ARCHIVE_NAME=$2

sed -i "s/0.0.0/${VERSION}/g" config*.xml shootime.php
mkdir -p shootime

find . -not -name "shootime" \
       -not -name ".gitignore" \
       -not -name ".pre-commit-config.yaml" \
       -not -name ".php-cs-fixer.dist.php" \
       -not -path ".git/*" \
       -not -path ".github/*" \
       -not -path ".scripts/*" \
       -exec cp -t dest/ {} +

zip -r "$ARCHIVE_NAME" shootime

echo "Created $ARCHIVE_NAME"
