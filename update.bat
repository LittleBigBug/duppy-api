@ECHO off

:ask
set /P c=Do you want to update composer? [(Y) \ n]
if /I "%c%" EQU "N" goto :skip_update_composer

composer update
:skip_update_composer

set /P c=Do you want to update git? [(Y) \ n]
if /I "%c%" EQU "N" goto :skip_update_git

git pull
:skip_update_git

vendor/bin/doctrine.exe orm:schema-tool:update --force --dump-sql
vendor/bin/doctrine.exe orm:generate-proxies

PAUSE