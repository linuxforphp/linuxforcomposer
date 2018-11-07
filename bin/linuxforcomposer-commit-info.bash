#!/usr/bin/env bash
export idvalue=$1
export temp_filename=$2
docker ps --filter "id=$idvalue"
read -r -p "Commit container $idvalue? [y/N]" answer1
if [[ "$answer1" == "y" || "$answer1" == "Y" || "$answer1" == "yes" || "$answer1" == "YES" ]]; then
read -r -p "Please enter the name of the new commit:" answer2
read -r -p "Save to linuxforcomposer.json file? [y/N]" answer3
fi
echo "$answer1;$answer2;$answer3" > $temp_filename
