#!/bin/bash

gulp server 2>&1 | while read -r i
do
 echo "$i" >> /var/www/html/todo/gulp.out
done 2>&1