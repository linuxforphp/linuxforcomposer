@ECHO OFF
SET idvalue=%1
SET tempodir=%2
start /wait PowerShell -Command docker ps --filter "id=%idvalue%"; "Read-Host "Commit container %idvalue%? [y/N]" | Out-File %tempodir%temp1.txt -encoding ASCII"
SET /p answer1=<%tempodir%temp1.txt
IF "!answer1!" == "!y!" SET commitimage=true
IF "!answer1!" == "!Y!" SET commitimage=true
IF "!answer1!" == "!yes!" SET commitimage=true
IF "!answer1!" == "!YES!" SET commitimage=true
IF "!commitimage!" == "!true!" (
start /wait PowerShell -Command "Read-Host "Please enter the name of the new commit" | Out-File %tempodir%temp2.txt -encoding ASCII"; "Read-Host "Save to linuxforcomposer.json file? [y/N]" | Out-File %tempodir%temp3.txt -encoding ASCII"
SET /p answer2=<%tempodir%temp2.txt
SET /p answer3=<%tempodir%temp3.txt
)
echo %answer1%;%answer2%;%answer3%
