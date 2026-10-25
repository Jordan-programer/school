import urllib.request
import os
import sys

print("A descarregar Composer...")
composer_url = "https://getcomposer.org/download/latest-stable/composer.phar"
phar_path = r"C:\xampp\php\composer.phar"
bat_path = r"C:\xampp\php\composer.bat"

urllib.request.urlretrieve(composer_url, phar_path)
print(f"Sucesso: {phar_path} ({os.path.getsize(phar_path)} bytes)")

with open(bat_path, "w", encoding="utf-8") as f:
    f.write("@echo off\n")
    f.write(r'"C:\xampp\php\php.exe" "C:\xampp\php\composer.phar" %*')
    f.write("\n")

print(f"Sucesso: {bat_path} criado.")
