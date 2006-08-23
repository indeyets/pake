@echo off

rem *********************************************************************
rem ** the pake build script for Windows based systems (based on phing.bat)
rem ** $Id: pake.bat 82 2006-01-23 11:08:59Z fabien $
rem *********************************************************************

rem This script will do the following:
rem - check for PHP_COMMAND env, if found, use it.
rem   - if not found detect php, if found use it, otherwise err and terminate
rem - check for PAKE_HOME evn, if found use it
rem   - if not found error and leave
rem - check for PHP_CLASSPATH, if found use it
rem   - if not found set it using PAKE_HOME/lib

if "%OS%"=="Windows_NT" @setlocal

rem %~dp0 is expanded pathname of the current script under NT
set DEFAULT_PAKE_HOME=%~dp0..

goto init
goto cleanup

:init

if "%PAKE_HOME%" == "" set PAKE_HOME=%DEFAULT_PAKE_HOME%
set DEFAULT_PAKE_HOME=

if "%PHP_COMMAND%" == "" goto no_phpcommand
if "%PHP_CLASSPATH%" == "" goto set_classpath

goto run
goto cleanup

:run
IF EXIST "@PEAR-DIR@" (
  %PHP_COMMAND% -d html_errors=off -qC "@PEAR-DIR@\pake.php" %1 %2 %3 %4 %5 %6 %7 %8 %9
) ELSE (
  %PHP_COMMAND% -d html_errors=off -qC "%PAKE_HOME%\bin\pake.php" %1 %2 %3 %4 %5 %6 %7 %8 %9
)
goto cleanup

:no_phpcommand
REM echo ------------------------------------------------------------------------
REM echo WARNING: Set environment var PHP_COMMAND to the location of your php.exe
REM echo          executable (e.g. C:\PHP\php.exe).  (Assuming php.exe on Path)
REM echo ------------------------------------------------------------------------
set PHP_COMMAND=php.exe
goto init

:err_home
echo ERROR: Environment var PAKE_HOME not set. Please point this
echo variable to your local pake installation!
goto cleanup

:set_classpath
set PHP_CLASSPATH=%PAKE_HOME%\lib
goto init

:cleanup
if "%OS%"=="Windows_NT" @endlocal
REM pause
