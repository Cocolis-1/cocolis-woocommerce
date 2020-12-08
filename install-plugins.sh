#!/bin/bash
COMMAND="sudo -u www-data wp --path=${WORDPRESS_PATH}"
echo "Installing plugins and themes"

#Append below for the plugin installation

if [ $(${COMMAND} plugin is-installed woocommerce) ]; then
  echo "Update woocommerce plugin for web analytics"
  ${COMMAND} plugin update woocommerce --activate
else
  echo "Install woocommerce plugin for ecommerce"
  ${COMMAND} plugin install woocommerce --activate
fi
