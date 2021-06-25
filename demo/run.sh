#!/bin/bash

cd $(dirname $(dirname $(readlink -f $0)))

php describe.phar mysql://127.0.0.1/test_describer demo/ --config demo/config.php
