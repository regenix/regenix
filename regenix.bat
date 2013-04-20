@echo off

@setlocal

set REGENIX_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%REGENIX_PATH%regenix" %*

@endlocal