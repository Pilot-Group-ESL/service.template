#!/bin/bash

set -e

# Check unit test database
while ! mysql --protocol TCP -uservice_template_test_user -puser_secret_test_password -hservice-template-db-test -e "show databases;" > /dev/null 2>&1; do
       echo "Waiting for service-template-db-test connection. "
       sleep 5
done
