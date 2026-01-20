#!/bin/bash
# Create .env from .env.example if it doesn't exist
if [ ! -f ./.env ]; then
  if [ -f ./.env.example ]; then
    cp ./.env.example ./.env
    echo ".env created from .env.example"
  elif [ -f ./.env ]; then
    # Fallback to .env if .env.example doesn't exist
    cp ./.env ./.env
    echo ".env created from .env"
  else
    echo "Error: Neither .env.example nor .env file found"
    exit 1
  fi
fi

# Go through each variable in .env.example (or .env) and expand any variable references in .env
SOURCE_FILE="./.env.example"
if [ ! -f "$SOURCE_FILE" ]; then
  SOURCE_FILE="./.env"
fi

env_convert () {
  while IFS= read -r line; do
    # Skip comments and empty lines
    [[ "$line" =~ ^#.*$ ]] && continue
    [[ -z "$line" ]] && continue

    # Extract key and value
    if [[ "$line" =~ ^([^=]+)=(.*)$ ]]; then
      key="${BASH_REMATCH[1]}"
      value="${BASH_REMATCH[2]}"

      # Escape special characters for sed (using | as delimiter to avoid issues with /)
      escaped_value=$(echo "$value" | sed 's/[\/&]/\\&/g')

      # Replace env vars by values in .env using | as delimiter
      sed -i "s|\${$key}|$escaped_value|g" ./.env 2>/dev/null || true
    fi
  done < "$SOURCE_FILE"
}

# Run conversion twice to handle nested variable references
env_convert
env_convert

