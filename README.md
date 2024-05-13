# ISPConfig 3 Server Manager for FOSSBilling

This repository is for the ISPConfig 3 server manager for FOSSBilling.

This server manager has been re-written using the RESTful API.  This is a community developed and maintained package.

## Installation
1. Download the `Ispconfig3.php` file from the source code
2. Copy the downloaded `Ispconfig3.php` file to `/library/server/manager/Ispconfig3.php`in your FOSSBilling instance.

## Custom Package Values
- vat_id - Default: NULL
- web_php_options - Default: no,fast-cgi,cgi,mod,suphp,php-fpm (Not sure if all these options exist, I think only fast-cgi & php-fpm exists.  Please LMK if you know.)
- limit_shell_user - Default: 1
- ssh_chroot - Default: no,jailkit,ssh-chroot
- language - Default: en
