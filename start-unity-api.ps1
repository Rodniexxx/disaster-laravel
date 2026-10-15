$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
& 'C:\xampp\php\php.exe' artisan serve --host=0.0.0.0 --port=8001
