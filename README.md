
                               AuthLDAP
                              Version 1.0


What is AuthLDAP
-------------
  AuthLDAP is a plug-in to extend Avideo's user authentication by using 
  the authentication mechanism of an LDAP server (389 Directory Server 
  or Active Directory) that implements the LDAP protocol.


Requirements
------------
  The following softwares are required. 

  * PHP 8.0


Installation
------------
  Install unzip to install the plug-in.
  ```
  # dnf install unzip
  ```

  Install php-ldap for LDAP connections with php.
  # dnf install php-ldap

  Adjust the write rights of the plugin directory.
  # chown apache:apache /var/www/html/AVideo/plugin && sudo chmod 755 /var/www/html/AVideo/plugin

  If SELinux is enabled, the file context must be adjusted.
  # semanage fcontext -a -t httpd_sys_rw_content_t /var/www/html/AVideo/plugin
  # restorecon /var/www/html/AVideo/plugin

  Upload the AuthLDAP zip file in the plugin settings in the Avideo admin menu.

  
Download
--------
  https://github.com/designet-inc-oss/AuthLDAP


Homepage
--------
  https://www.designet.co.jp/open_source/authldap


Bug reports to
--------------
  https://github.com/designet-inc-oss/AuthLDAP/issues
