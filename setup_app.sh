#!/bin/bash

# Assumptions apache/php/mysql have already been installed and configured
# Usage ./setup_app.sh siteName db_user db_password

SITE="$1"
#DB_NAME="$SITE"
#DB_USER="$2"
#DB_PASS="$3"

PROJ=`pwd`

if [ ! -f /etc/apache2/sites-available/$SITE ]; then
  echo "---------------------------------------"
  echo "Configuring Apache2 VirtualHost"
  sudo tee /etc/apache2/sites-available/$SITE <<EOF > /dev/null
    <VirtualHost *:80>
      ServerAdmin webmaster@dummy-host.example.com

      ServerName      $SITE
      DocumentRoot    $PROJ

      DirectoryIndex index.html

      <Directory $PROJ>
        Options FollowSymLinks
        AllowOverride All
      </Directory>
    </VirtualHost>
EOF
  echo "---------------------------------------"
else 
  echo "---------------------------------------"
  echo "Skipping Apache2 VirtualHost file"
  echo "---------------------------------------"
fi

a2dissite default
a2enmod rewrite
a2ensite $SITE
service apache2 restart
echo

#echo "---------------------------------------"
#echo "Create database and tables"
#echo "create database ${DB_NAME}" | mysql -u$DB_USER -p$DB_PASS 
#mysql -u$DB_USER -p$DB_PASS $DB_NAME < $PROJ/doc/campsite_php.sql
#echo "---------------------------------------"

#sed -i "s/\(\['DB_HOST'\]\s*=\s*\).*/\1\'$DB_NAME\';/" includes/sm_config.inc
#sed -i "s/\(\['DB_PASSWORD'\]\s*=\s*\).*/\1\'$DB_PASS\';/" includes/sm_config.inc
#sed -i "s/\(\['DB_SERVERNAME'\]\s*=\s*\).*/\1\'$DB_USER\';/" includes/sm_config.inc
