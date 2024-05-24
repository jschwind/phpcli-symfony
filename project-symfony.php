<?php

const COLORS=['GREEN'=>"\033[32m", 'RED'=>"\033[31m", 'NONE'=>"\033[0m",];
const NL="\n";

$rp=realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR;

if ($argc<4) {
	echo COLORS['RED'].'Missing arguments.'.COLORS['NONE'].NL;
	echo 'Usage: php project.php <project-name> <git-username> <git-email>'.NL;
	echo 'Example: php project.php my-project username username@domain.tld'.NL;
	exit(1);
}

$project_name = $argv[1];
$git_username = $argv[2];
$git_email = $argv[3];
if (isset($argv[4])) {
	$php_version = $argv[4];
} else {
	$php_version = '8.2';
}
if (isset($argv[5])) {
	$mysql_version = $argv[5];
} else {
	$mysql_version = '11.3';
}



$o=$rp.'output'.DIRECTORY_SEPARATOR.$argv[1].DIRECTORY_SEPARATOR;

if (!is_dir($o)) {
    mkdir($o, 0777, true);
}

if (isset($argv[6])) {
    $o=$argv[6];
}

if (substr($o, -1) !== DIRECTORY_SEPARATOR) {
    $o .= DIRECTORY_SEPARATOR;
}

echo $path;

if (preg_match('/^[a-zA-Z0-9\-\_\.]+$/', $project_name) === 0) {
	echo COLORS['RED'].'Invalid project name. [a-zA-Z0-9-_.]'.COLORS['NONE'].NL;
	exit(1);
}

if (preg_match('/^[a-zA-Z0-9\-\_\.]+$/', $git_username) === 0) {
	echo COLORS['RED'].'Invalid git username. [a-zA-Z0-9-_.]'.COLORS['NONE'].NL;
	exit(1);
}

if (filter_var($git_email, FILTER_VALIDATE_EMAIL) === false) {
	echo COLORS['RED'].'Invalid git email.'.COLORS['NONE'].NL;
	exit(1);
}

file_put_contents($o.'.gitattributes', <<<EOF
*.css text eol=lf
*.htaccess text eol=lf
*.htm text eol=lf
*.html text eol=lf
*.js text eol=lf
*.json text eol=lf
*.map text eol=lf
*.md text eol=lf
*.php text eol=lf
*.profile text eol=lf
*.script text eol=lf
*.sh text eol=lf
*.svg text eol=lf
*.txt text eol=lf
*.xml text eol=lf
*.yml text eol=lf
EOF
);
chmod($o.'.gitattributes', 0777);

file_put_contents($o.'docker-compose.yml', <<<EOF
version: "3.9"
services:
    web:
        build: ./docker/web
        working_dir: /app
        user: application
        ports:
            - "80:80"
            - "443:443"
        volumes:
            - ./:/app
        tmpfs:
          - /tmp:mode=1777
        environment:
          - WEB_DOCUMENT_ROOT=/app/public
          - PHP_DISPLAY_ERRORS=1
          - PHP_MEMORY_LIMIT=512M
          - PHP_MAX_EXECUTION_TIME=300
          - PHP_POST_MAX_SIZE=200M
          - PHP_UPLOAD_MAX_FILESIZE=100M
    mariadb:
        image: mariadb:{$mysql_version}
        environment:
            MYSQL_ROOT_PASSWORD: root
            MYSQL_DATABASE: my_database
            MYSQL_USER: my_user
            MYSQL_PASSWORD: my_password
        volumes:
            - ./docker/mariadb:/docker-entrypoint-initdb.d
            - ./docker/mariadb/data:/var/lib/mysql
        ports:
            - "3306:3306"
EOF
);
chmod($o.'docker-compose.yml', 0777);

file_put_contents($o.'README.md', <<<EOF
# {$project_name}

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
EOF
);
chmod($o.'README.md', 0777);

if (!is_dir($o.'docker'.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR)) {
	mkdir($o.'docker'.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR, 0777, true);
}
file_put_contents($o.'docker'.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'Dockerfile', <<<EOF
FROM webdevops/php-apache-dev:{$php_version}

# Update and install
RUN apt-get update && apt-get install -y

#Nano
RUN apt-get install -y nano

#Keyring
RUN mkdir -p /etc/apt/keyrings

# Node.js
RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
RUN NODE_MAJOR=18 && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_\$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list

# Yarn
RUN curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor |  tee /usr/share/keyrings/yarnkey.gpg >/dev/null 2>&1
RUN echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian stable main" |  tee /etc/apt/sources.list.d/yarn.list >/dev/null 2>&1
RUN apt-get update && apt-get install -y yarn

# Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt-get update && apt-get install -y symfony-cli

# Git config
USER application
RUN git config --global user.email "{$git_email}"
RUN git config --global user.name "{$git_username}"
EOF
);
chmod($o.'docker'.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'Dockerfile', 0777);

file_put_contents($o.'docker'.DIRECTORY_SEPARATOR.'bash.bat', <<<EOF
docker exec --user=application -it -w /app {$project_name}-web-1 bash
EOF
);
chmod($o.'docker'.DIRECTORY_SEPARATOR.'bash.bat', 0777);

file_put_contents($o.'docker'.DIRECTORY_SEPARATOR.'bash.sh', <<<EOF
#!/bin/bash
docker exec --user=application -it -w /app {$project_name}-web-1 bash
EOF
);
chmod($o.'docker'.DIRECTORY_SEPARATOR.'bash.sh', 0777);

file_put_contents($o.'docker'.DIRECTORY_SEPARATOR.'root.bat', <<<EOF
docker exec --user=root -it -w /app {$project_name}-web-1 bash
EOF
);
chmod($o.'docker'.DIRECTORY_SEPARATOR.'root.bat', 0777);

file_put_contents($o.'docker'.DIRECTORY_SEPARATOR.'root.sh', <<<EOF
#!/bin/bash
docker exec --user=root -it -w /app {$project_name}-web-1 bash
EOF
);
chmod($o.'docker'.DIRECTORY_SEPARATOR.'root.sh', 0777);

echo COLORS['GREEN'].$project_name.' created.'.COLORS['NONE'].NL;
exit(1);

?>