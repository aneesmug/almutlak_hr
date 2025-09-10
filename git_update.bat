@echo off
echo Running git add...
git add .

set /p commit_reason="Enter commit reason: "

echo Committing changes...
git commit -m "%commit_reason%"

echo Pushing to origin main...
git push origin main

echo Git update completed!
#pause