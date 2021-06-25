#!/bin/bash

cd $(dirname $(dirname $(readlink -f $0)))

mysqldump test_describer --databases --add-drop-database --skip-comments > demo/mysql.sql
sed -i -e 's/DEFINER=`.*`@`.*`//g' demo/mysql.sql
