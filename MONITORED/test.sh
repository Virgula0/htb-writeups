folder=$1
folder=$(echo "$folder" | sed -e 's/[^[:alnum:]|-]//g')
echo $folder
echo $a
tail "$a"
