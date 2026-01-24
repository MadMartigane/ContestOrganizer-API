# ContestOrganizer-API
API for the ContestOrganizer project

## Allow HTTPD to write on file system

```sudo setsebool -P httpd_anon_write on```

### Set the right user for php-fpm (likely the same as the webserver one)

```sudo vim /etc/php-fpm.d/www.conf```

```bash
; RPM: apache user chosen to provide access to the same directories as httpd
user = nginx
; RPM: Keep a group allowed to write in log dir.
group = nginx
```

```sudo systemctl restart nginx```
```sudo systemctl restart php-fpm.service```


