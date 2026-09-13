#!/usr/bin/env pwsh
# .\scripts\ci.ps1
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Write-Host "Running lint and format checks..." -ForegroundColor Cyan

# Run ruff format and check
uv run ruff format --check .
if ($LASTEXITCODE -ne 0) {
    Write-Error "ruff format check failed"
    exit 1
}
uv run ruff check --fix .
if ($LASTEXITCODE -ne 0) {
    Write-Error "ruff check failed"
    exit 1
}

# Run type checking
uv run ty check
if ($LASTEXITCODE -ne 0) {
    Write-Error "type check failed"
    exit 1
}

# Run tests
uv run pytest -v --tb=short
if ($LASTEXITCODE -ne 0) {
    Write-Error "tests failed"
    exit 1
}

Write-Host "All checks passed!" -ForegroundColor Green