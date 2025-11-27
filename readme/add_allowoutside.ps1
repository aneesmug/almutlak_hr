# PowerShell script to add allowOutsideClick: false to all Swal.fire() calls
# This script processes all JavaScript files and adds the property where missing

$jsDir = "d:\xampp\htdocs\almutlak\system\assets\js"
$files = @('resignationApproval.js', 'resignationApprovalWizard.js', 'resignationWizard.js', 'loan_approval.js', 'loanHandling.js', 'employee_profile.js', 'createUser.js', 'jquery.app.js')

foreach ($file in $files) {
    $filePath = Join-Path $jsDir $file
    if (-not (Test-Path $filePath)) {
        Write-Host "File not found: $filePath"
        continue
    }
    
    $content = Get-Content $filePath -Raw
    $originalLength = $content.Length
    
    # Pattern: Find Swal.fire({ ... }) where allowOutsideClick is NOT already present
    # We need to find the closing bracket of the fire() object and add allowOutsideClick before it
    
    # Split by Swal.fire({
    $parts = $content -split '(?=Swal\.fire\(\{)'
    $processed = @()
    
    foreach ($part in $parts) {
        if ($part -match '^Swal\.fire\(\{') {
            # Find the matching closing bracket for this Swal.fire() call
            $braceCount = 0
            $inString = $false
            $stringChar = ''
            $endPos = 0
            $chars = $part.ToCharArray()
            
            for ($i = 0; $i -lt $chars.Length; $i++) {
                $char = $chars[$i]
                
                # Handle string escaping
                if ($char -eq '\' -and $i + 1 -lt $chars.Length) {
                    $i++ # Skip escaped character
                    continue
                }
                
                # Handle string quotes
                if (($char -eq '"' -or $char -eq "'") -and -not $inString) {
                    $inString = $true
                    $stringChar = $char
                } elseif ($char -eq $stringChar -and $inString) {
                    $inString = $false
                }
                
                # Count braces only if not in string
                if (-not $inString) {
                    if ($char -eq '{') {
                        $braceCount++
                    } elseif ($char -eq '}') {
                        $braceCount--
                        if ($braceCount -eq 0) {
                            $endPos = $i
                            break
                        }
                    }
                }
            }
            
            if ($endPos -gt 0) {
                $fireCall = $part.Substring(0, $endPos)
                $rest = $part.Substring($endPos)
                
                # Check if allowOutsideClick already exists
                if ($fireCall -notmatch 'allowOutsideClick\s*:') {
                    # Add allowOutsideClick: false before the closing bracket
                    $fireCall = $fireCall -replace '}$', ",`n        allowOutsideClick: false`n    }"
                }
                
                $processed += $fireCall + $rest
            } else {
                $processed += $part
            }
        } else {
            $processed += $part
        }
    }
    
    $newContent = $processed -join ""
    
    if ($newContent.Length -ne $originalLength) {
        Set-Content $filePath $newContent -Encoding UTF8 -NoNewline
        $diff = $newContent.Length - $originalLength
        Write-Host "Updated: $file (added $($diff) characters)"
    } else {
        Write-Host "No changes needed: $file"
    }
}

Write-Host "Complete!"
