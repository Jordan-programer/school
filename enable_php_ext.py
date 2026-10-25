import re

ini_path = r"C:\xampp\php\php.ini"
with open(ini_path, "r", encoding="utf-8", errors="ignore") as f:
    content = f.read()

extensions = [
    "pdo_pgsql",
    "pgsql",
    "fileinfo",
    "zip",
    "curl",
    "mbstring",
    "openssl",
    "gd",
    "intl",
    "sodium",
]

for ext in extensions:
    # Descomentar ;extension=ext ou ;extension=php_ext.dll
    pattern = rf";\s*extension\s*=\s*(php_)?{ext}(\.dll)?"
    replacement = rf"extension={ext}"
    content = re.sub(pattern, replacement, content, flags=re.IGNORECASE)

with open(ini_path, "w", encoding="utf-8") as f:
    f.write(content)

print("php.ini actualizado com sucesso.")
