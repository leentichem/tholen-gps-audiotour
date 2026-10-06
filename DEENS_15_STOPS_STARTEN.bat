@echo off
chcp 65001 >nul
title THOLEN - DEENS - 15 STOPS

cd /d "%~dp0"

echo ==========================================
echo THOLEN - DEENSE AUDIO - 15 STOPS
echo ==========================================
echo.
echo Dit gebruikt het bestaande werkende
echo Supertonic-programma.
echo De fout "16 vertalingen" wordt tijdelijk omzeild.
echo.

if not exist "MAAK_AUDIO_DA_SUPERTONIC.bat" (
  echo FOUT: MAAK_AUDIO_DA_SUPERTONIC.bat staat niet in deze map.
  echo.
  echo Dit bestand moet rechtstreeks in:
  echo Downloads\THOLEN_ECHTE_EINDVERSIE
  echo staan.
  pause
  exit /b 1
)

if not exist "teksten\da\stop-15.txt" (
  echo FOUT: teksten\da\stop-15.txt ontbreekt.
  pause
  exit /b 1
)

if exist "teksten\da\stop-16.txt" del /q "teksten\da\stop-16.txt" >nul 2>&1
if exist "audio\da\stop-16.mp3" del /q "audio\da\stop-16.mp3" >nul 2>&1

echo Tijdelijke stop 16 wordt gemaakt...
copy /y "teksten\da\stop-15.txt" "teksten\da\stop-16.txt" >nul
if errorlevel 1 goto fout

echo.
echo Het bestaande Deense Supertonic-programma wordt nu gestart.
echo Wacht tot het klaar is.
echo.

call "MAAK_AUDIO_DA_SUPERTONIC.bat"
set "RESULT=%ERRORLEVEL%"

del /q "teksten\da\stop-16.txt" >nul 2>&1
del /q "audio\da\stop-16.mp3" >nul 2>&1

echo.
if "%RESULT%"=="0" (
  echo ==========================================
  echo KLAAR.
  echo Deense audio voor stop 01 t/m 15 is gemaakt.
  echo De tijdelijke stop 16 is verwijderd.
  echo ==========================================
) else (
  echo ==========================================
  echo FOUTCODE: %RESULT%
  echo De tijdelijke stop 16 is verwijderd.
  echo ==========================================
)

pause
exit /b %RESULT%

:fout
echo.
echo FOUT: tijdelijke stop 16 kon niet worden gemaakt.
pause
exit /b 1
