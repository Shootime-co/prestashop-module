#!/usr/bin/env bash

VERSION=$1
ARCHIVE_NAME=$2
DEST=shootime

sed -i "s/0.0.0/${VERSION}/g" config*.xml shootime.php
mkdir -p $DEST

find . \
  -not -name ".gitignore" \
  -not -name ".pre-commit-config.yaml" \
  -not -name ".php-cs-fixer.dist.php" \
  -not -name ".DS_Store" \
  -not -path "./shootime*" \
  -not -path "./.idea*" \
  -not -path "./.git*" \
  -not -path "./.github*" \
  -not -path "./.scripts*" \
  -exec cp -t $DEST {} +

zip -r "$ARCHIVE_NAME" $DEST

echo "Created $ARCHIVE_NAME"
