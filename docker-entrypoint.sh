#!/bin/bash

# init config dir
is_empty_dir(){ 
    return `ls -A $1|wc -w`
}
if is_empty_dir /var/www/html/config 
then
  cp -a /var/www/html/config_src/* /var/www/html/config
fi

exec apache2-foreground