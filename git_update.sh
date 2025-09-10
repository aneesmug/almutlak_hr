#!/bin/bash

echo "Running git add..."
git add .

# Prompt for commit message
read -p "Enter commit reason: " commit_reason

echo "Committing changes..."
git commit -m "$commit_reason"

echo "Pushing to origin main..."
git push origin main

echo "Git update completed!"