@echo off
echo ========================================================
echo   GitHub Push Helper
echo ========================================================
echo.
echo This script will help you push your code to GitHub.
echo.
echo IMPORTANT: You need a GitHub Personal Access Token
echo Get it from: https://github.com/settings/tokens/new
echo   - Check "repo" scope
echo   - Copy the token
echo.
pause
echo.

cd /d "%~dp0"

echo Step 1: Adding GitHub remote...
git remote remove origin 2>nul
git remote add origin https://github.com/naik03shashank/hk-checklist.git

echo.
echo Step 2: Pushing to GitHub...
echo When prompted:
echo   Username: naik03shashank
echo   Password: [Paste your Personal Access Token]
echo.

git push -u origin main

echo.
echo ========================================================
if %ERRORLEVEL% EQU 0 (
    echo SUCCESS! Code pushed to GitHub!
    echo.
    echo Next: Go to https://dashboard.render.com/
    echo   1. Sign in with: naikshashank211@gmail.com
    echo   2. Click "New +" - "Web Service"
    echo   3. Connect your GitHub repo: hk-checklist
    echo   4. Select Docker runtime
    echo   5. Click "Create Web Service"
    echo.
    echo You'll get your 24/7 URL in 5-10 minutes!
) else (
    echo FAILED! Please check the error above.
    echo.
    echo Common issues:
    echo   - Wrong token or username
    echo   - Repository doesn't exist on GitHub
)
echo ========================================================
pause
