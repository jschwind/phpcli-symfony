# myproject

### Setup Docker
run `docker compose up` to build and run the container

### Setup Symfony
- run `docker/bash.sh` to get into the container

#### inside the container run
- `composer create-project symfony/skeleton:"6.*" apptemp` to create a new symfony project
- `mv /app/apptemp/* /app/` to move the files from the temp folder to the root folder
- `find /app/apptemp/ -name ".*" ! -name . ! -name .. -exec mv {} /app/ \;` to move the hidden files from the temp folder to the root folder
- `rm -R /app/apptemp` to remove the temp folder

#### mariadb gitignore
- run `echo "/docker/mariadb/" >> .gitignore` to ignore the mariadb folder
- run `echo "/.idea/" >> .gitignore` to ignore the idea folder

#### inside the container setup symfony
- `composer require webapp` to install the webapp bundle
- `composer require symfony/apache-pack` to install the apache pack