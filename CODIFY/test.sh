DB_USER="root"
DB_PASS="super_secret_not_known_psw"

read -p "Enter MySQL password for $DB_USER: " USER_PASS
/usr/bin/echo $USER_PASS

if [[ $DB_PASS == $USER_PASS ]]; then
        /usr/bin/echo "Password confirmed!"
else
        /usr/bin/echo "Password confirmation failed!"
        exit 1
fi
