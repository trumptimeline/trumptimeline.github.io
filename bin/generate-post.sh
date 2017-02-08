#!/bin/bash

dir=$(pwd)
dir_bin="bin"
dir_post="_post"
dir_screenshot="screenshots"
file_template="_post/_template.md"

if [ "$1" != '' ]; then
 	url="$1"
 	echo " Post Source URL: $url"
else
	read -p " Post Source URL: " url
fi
if [ "$2" != '' ]; then
 	file_target="$2"
 	echo "     Target File: $file_target"
else
	read -p "     Target File: " file_target
fi
read -p "     Source Name: " tags
read -p "      Post Title: " title
read -p "Post Description: " description
read -p "       Post Tags: " tags
read -p "Continue? [Y/n] " yn
if [[ $yn =~ ^[Nn]$ ]]; then
	exit;
fi

screenshot_result=$(cd $dir_bin;./phantomjs screenshot.js "$url" -p "$dir_screenshot")
# echo "$screenshot_result"
# screenshot_path=$(echo $screenshot_result | perl -pe 's/^.*Path: (.*?)($|\r.*$)/$1/m')
# screenshot_file=$(echo $screenshot_result | perl -pe 's/^.*File: (.*?)($|\r.*$)/$1/m')
# page_title=$(echo $screenshot_result | perl -pe 's/^.*Title: (.*?)($|\r.*$)/$1/m')

nl='([^\
]*)'
match="Title: $nl"
if [[ "$screenshot_result" =~ $match ]]; then
  page_title="${BASH_REMATCH[1]}"
fi
match="Path: $nl"
if [[ "$screenshot_result" =~ $match ]]; then
  screenshot_path="${BASH_REMATCH[1]}"
fi
match="File: $nl"
if [[ "$screenshot_result" =~ $match ]]; then
  screenshot_file="${BASH_REMATCH[1]}"
fi
match="og:description: $nl"
if [[ "$screenshot_result" =~ $match ]]; then
  page_description="${BASH_REMATCH[1]}"
fi

if [ "$screenshot_path" == '' ]; then
	echo "Screenshot Failed";
	exit;
fi
if [ "$title" = '' ]; then
	title=$page_title;
fi
if [ "$description" = '' ]; then
	description=$page_description;
fi

echo "Writing $target_file"
echo "Title: $title"
echo "Discription: $description"
echo "Screenshot: $screenshot_path"
echo "Tags: [$tags]"

cp $file_template $target_file
sed -i 's/\{title\}/'"$title"/g' $target_file