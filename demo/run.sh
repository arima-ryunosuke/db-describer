#!/bin/bash

# export: mysqlpump sakila --add-drop-database --skip-definer > demo/sakila.sql
# import: mysql     sakila < demo/sakila.sql

cd $(dirname $(dirname $(readlink -f $0)))

php describe.phar mysql://127.0.0.1/sakila demo/ --config demo/config.php
