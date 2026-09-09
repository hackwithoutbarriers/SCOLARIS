#!/bin/sh
set -eu

exec php artisan schedule:run
