import re

# Read the file
with open('jquery.app.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace color hex codes with APP_COLORS constants
replacements = [
    (r"confirmButtonColor:\s*'#3085d6'", "confirmButtonColor: APP_COLORS.primary"),
    (r"confirmButtonColor:\s*'#28a745'", "confirmButtonColor: APP_COLORS.success"),
    (r"confirmButtonColor:\s*'#4e73df'", "confirmButtonColor: APP_COLORS.primary"),
    (r"confirmButtonColor:\s*'#17a2b8'", "confirmButtonColor: APP_COLORS.info"),
    (r"cancelButtonColor:\s*'#d33'", "cancelButtonColor: APP_COLORS.danger_dark"),
    (r"cancelButtonColor:\s*'#6c757d'", "cancelButtonColor: APP_COLORS.secondary"),
    (r"cancelButtonColor:\s*'#e74a3b'", "cancelButtonColor: APP_COLORS.danger"),
    # Additional inline color replacements
    (r"'#3085d6'", "APP_COLORS.primary"),
    (r"'#d33'", "APP_COLORS.danger_dark"),
    (r"'#28a745'", "APP_COLORS.success"),
    (r"'#dc3545'", "APP_COLORS.danger"),
    (r"'#6c757d'", "APP_COLORS.secondary"),
    (r"'#f8f9fa'", "APP_COLORS.bg_lighter"),
    (r"'#e9ecef'", "APP_COLORS.bg_light"),
    (r"'#ced4da'", "APP_COLORS.border_default"),
    (r"'#333333'", "APP_COLORS.text_dark"),
]

original_length = len(content)
for pattern, replacement in replacements:
    content = re.sub(pattern, replacement, content)
    before = len(content)

# Write back
with open('jquery.app.js', 'w', encoding='utf-8') as f:
    f.write(content)

print("Color replacements in jquery.app.js completed successfully!")
print(f"Original content length: {original_length} bytes")
print(f"New content length: {len(content)} bytes")
