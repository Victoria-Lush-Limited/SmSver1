@echo off
setlocal
REM Applies shared-schema SQL for VLL app + portal. Edit DB / MYSQL if needed.
set "DB=anderson_vllsms"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
if not exist "%MYSQL%" set "MYSQL=mysql"

cd /d "%~dp0"
echo [%DB%] create_incoming_table_if_missing.sql
"%MYSQL%" -u root "%DB%" < create_incoming_table_if_missing.sql || exit /b 1
echo [%DB%] alter_incoming_segment_status.sql
"%MYSQL%" -u root "%DB%" < alter_incoming_segment_status.sql || exit /b 1
echo [%DB%] ensure_app_bridge_tables.sql
"%MYSQL%" -u root "%DB%" < ensure_app_bridge_tables.sql || exit /b 1
echo Done. Run baseline_laravel_migrations_safe.sql separately if Laravel migrate conflicts with SmSver1.
exit /b 0
