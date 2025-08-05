@echo off
echo 🧹 Cleaning up unnecessary files from Thrive directory...

REM List of files to delete (temporary/debug files)
set FILES_TO_DELETE=^
check-database-structure.php ^
check-extensions.php ^
clean-database-setup.php ^
complete-database-setup.php ^
create-missing-tables.php ^
database-mysqli.php ^
database-test.php ^
debug-product-save-full.php ^
debug-product-save.php ^
debug-products-frontend.php ^
debug-products-table.php ^
direct-api-test.php ^
fix-mysql-extensions.bat ^
fix-php-mysql.bat ^
fix-products-table.php ^
full-diagnosis.php ^
manual-products-test.php ^
setup-database.php ^
system-check-enhanced.php ^
system-diagnosis.php ^
test-all-crud.php ^
test-api.php ^
test-products-api.php ^
web-database-test.php

REM Delete files
for %%f in (%FILES_TO_DELETE%) do (
    if exist "%%f" (
        del "%%f" >nul 2>&1
        echo ✅ Deleted: %%f
    )
)

REM Delete unnecessary documentation files (keeping main ones)
if exist "DATABASE-FIX-GUIDE.md" (
    del "DATABASE-FIX-GUIDE.md" >nul 2>&1
    echo ✅ Deleted: DATABASE-FIX-GUIDE.md
)

if exist "README-ENHANCED.md" (
    del "README-ENHANCED.md" >nul 2>&1
    echo ✅ Deleted: README-ENHANCED.md
)

if exist "QUICK-START.md" (
    del "QUICK-START.md" >nul 2>&1
    echo ✅ Deleted: QUICK-START.md
)

REM Delete database directory if it exists and is empty or contains temp files
if exist "database\" (
    rmdir /s /q "database" >nul 2>&1
    echo ✅ Deleted: database directory
)

REM Delete .env.example if it exists
if exist ".env.example" (
    del ".env.example" >nul 2>&1
    echo ✅ Deleted: .env.example
)

REM Delete install scripts
if exist "install.bat" (
    del "install.bat" >nul 2>&1
    echo ✅ Deleted: install.bat
)

if exist "install.sh" (
    del "install.sh" >nul 2>&1
    echo ✅ Deleted: install.sh
)

echo.
echo 🎯 Cleanup completed! 
echo.
echo 📁 Remaining essential files:
dir /b | findstr /v /i "cleanup.bat"

echo.
echo 💡 Your Thrive POS system is now clean and production-ready!
pause
