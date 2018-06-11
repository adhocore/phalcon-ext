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

# start redis server
redis-server &

# start php server
php -S localhost:1234 -t example &
```

Then visit [localhost:1234](http://localhost:1234)

PS: Make sure `example/.var/` and sub folders are writable.
