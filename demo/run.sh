#!/bin/bash

cd $(dirname $(dirname $(readlink -f $0)))

php describe.phar mysql://127.0.0.1/test_describer demo/ --mode all  --config demo/config.php
php describe.phar mysql://127.0.0.1/test_describer demo/ --mode html --config demo/config.php
