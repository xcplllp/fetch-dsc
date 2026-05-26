@echo off
title DSC Helper - One-Click Installer
color 0b
echo =============================================================
echo           ASA DIGITAL SIGNATURE (DSC) HELPER SETUP
echo =============================================================
echo.
echo Installing background hardware listener utility...
echo.

:: 1. Create permanent directory
mkdir C:\DscHelper >nul 2>&1
copy /Y DscHelper.exe C:\DscHelper\DscHelper.exe >nul
if %errorlevel% neq 0 (
    color 0c
    echo [ERROR] Failed to install. Please right-click this Setup.bat and choose "Run as Administrator".
    echo.
    pause
    exit
)

:: 2. Create silent VBScript in Windows Startup Folder
set "VBS_PATH=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\DscHelperSilent.vbs"
echo Set WshShell = CreateObject("WScript.Shell") > "%VBS_PATH%"
echo WshShell.Run "C:\DscHelper\DscHelper.exe", 0, false >> "%VBS_PATH%"

:: 3. Launch the background service immediately
wscript.exe "%VBS_PATH%"

color 0a
echo =============================================================
echo [SUCCESS] DSC Helper successfully installed on this PC!
echo =============================================================
echo.
echo * It is now running silently in the background.
echo * It will automatically start every time you turn on your PC.
echo * You do NOT need to open any files or windows again.
echo.
echo You can close this window now. Enjoy!
echo.
pause
