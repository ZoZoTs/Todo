#!/bin/bash

echo "killing..."
killall gulp

echo "running..."
#echo "gulp server > /var/www/html/todo/gulp.out 2>&1" | at now
echo "" > /var/www/html/todo/gulp.out

echo "/var/www/html/todo/gulp.sh >> /var/www/html/todo/gulp.out" | at now


echo "output....."
sleep 5
cat /var/www/html/todo/gulp.out