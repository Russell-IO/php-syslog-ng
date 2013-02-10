#!/bin/bash
# TH: This is an example for importing archived Data into the database again
#     2010-12-30 You need the dialog package.

_temp="/tmp/answer.$$"
_import="/tmp/import.$$"
PN=`basename "$0"`
VER='0.2'

tailbox() {
    dialog --backtitle "Import Log"\
           --begin 3 5 --title " Importing $selection "\
           --tailbox $_import 18 70
}

file_menu() {
    menuitems=''
    fileroot='/path_to_logzilla/exports/'
    IFS_BAK=$IFS
    IFS=$'\n' # wegen Filenamen mit Blanks
    array=( $(ls $fileroot) )
    n=0
    for item in ${array[@]}
    do
        menuitems="$menuitems $n ${item// /_}" # subst. Blanks with "_"  
        let n+=1
    done
    IFS=$IFS_BAK
    dialog --backtitle "Files found in the online store" \
           --title "Select a file" --menu \
           "Choose one archive" 16 40 8 $menuitems 2> $_temp
    if [ $? -eq 0 ]; then
        item=`cat $_temp`
        selection=${array[$(cat $_temp)]}
        sh /path_to_logzilla/scripts/import.sh ${selection%.gz} >$_import & 
	tailbox;
    fi
}


### create main menu using dialog
main_menu() {
    dialog --backtitle "Logzilla Database Import" --title " Main Menu - V. $VER "\
        --cancel-label "Quit" \
        --menu "Move using [UP] [DOWN], [Enter] to select" 17 60 10\
        Import_Menu "Import files from archive to logzilla"\
        Quit "Exit program" 2>$_temp
        
    opt=${?}
    if [ $opt != 0 ]; then rm $_temp; exit; fi
    menuitem=`cat $_temp`
    echo "menu=$menuitem"
    case $menuitem in
        Import_Menu) file_menu;;
        Quit) rm $_temp $_import; exit;;
    esac
}

while true; do
  main_menu
done
