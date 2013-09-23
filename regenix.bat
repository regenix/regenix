@echo off

@setlocal

set REGENIX_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe
if "%PHING_COMMAND%" == "" set PHING_COMMAND=%REGENIX_PATH%framework/vendor/Phing/bin/phing.bat

"%PHP_COMMAND%" -q "%REGENIX_PATH%regenix" %*

@endlocal