mysqldump test_describer --databases --add-drop-database --skip-comments > mysql.sql
sed -i -e 's/DEFINER=`.*`@`.*`//g' mysql.sql
