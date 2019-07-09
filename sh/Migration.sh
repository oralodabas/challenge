#!/usr/bin/env bash

php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --purge-with-truncate