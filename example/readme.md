## example

The code snippets here is quick dirty show case of the usage and features of **adhocore/phalcon_ext**. It is strictly just for reference and quick guide to skim through.

DONT COPY PASTE THESE EXAMPLE SNIPPETS INTO YOUR CODE.

## preview

Go to the root of this project and run

```sh
# prepare paths
mkdir -p example/.var/mail example/.var/sql example/.var/view
touch example/.var/db.db

# composer
composer install -o

# setup db
php example/setup.php

# start redis server
redis-server &

# start php server
php -S localhost:1234 -t example &

# to clear all redis cache
redis-cli -n 0 flushall
```

Then visit [localhost:1234](http://localhost:1234)

PS: Make sure `example/.var/` and sub folders are writable.

## cli

To test the example cli app, run below commands in terminal from project root:

```sh
example/cli --version
example/cli --help

example/cli main
example/cli main --name hello

example/cli main run --help
example/cli main run --config config.php

# OR
example/cli main:run --help
example/cli main:run --config config.php


# short option:
example/cli -V
example/cli -h
```
