#!/usr/bin/env bash
set -euo pipefail

echo "Running lint and format checks..."
# Run ruff format and check
uv run ruff format --check .
uv run ruff check --fix .

# Run type checking
uv run ty check

# Run tests
uv run pytest -v --tb=short

echo "All checks passed!"