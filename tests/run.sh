#!/bin/bash
#
# Ivan Shcherbak <dev@funivan.com> 2013
#
# All params goes into phpunit
#
# ./run.sh -h
# ./run.sh --stop-on-error

dir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
$dir/../vendor/bin/phpunit --configuration=$dir/phpunit.xml $@