<?php

class ProjectSetup
{
    const COLORS = ['GREEN' => "\033[32m", 'RED' => "\033[31m", 'NONE' => "\033[0m",];
    const NL = "\n";
    const DB_TYPES = ['mysql', 'postgres', 'mariadb'];

    private ?string $projectName;
    private ?string $gitUsername;
    private ?string $gitEmail;
    private string $phpVersion;
    private string $mysqlVersion;
    private string $postgresVersion;
    private string $dbType;
    private string $outputDir;
    private string $rootPath;
    private bool $isSH;

    public function __construct($options)
    {
        $this->rootPath = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR;
        $this->setOptions($options);
        $this->validateInputs();
        $this->setOutputDir();
    }

    private function setOptions($options)
    {
        $this->projectName = (isset($options['project-name']) ? $options['project-name'] : null);
        $this->gitUsername = (isset($options['git-username']) ? $options['git-username'] : null);
        $this->gitEmail = (isset($options['git-email']) ? $options['git-email'] : null);
        $this->phpVersion = (isset($options['php-version']) ? $options['php-version'] : '8.3');
        $this->postgresVersion = (isset($options['postgres-version']) ? $options['postgres-version'] : '16.3');
        $this->mysqlVersion = (isset($options['mysql-version']) ? $options['mysql-version'] : '8.4');
        $this->mariadbVersion = (isset($options['mariadb-version']) ? $options['mariadb-version'] : '11.4');
        $this->dbType = (isset($options['db-type']) ? $options['db-type'] : 'mariadb');
        $this->outputDir = (isset($options['output-dir']) ? $options['output-dir'] : $this->rootPath.'output'.DIRECTORY_SEPARATOR.$this->projectName.DIRECTORY_SEPARATOR);
        $this->isSH = (isset($options['is-sh']) ? (bool)$options['is-sh'] : false);
    }

    private function validateInputs()
    {
        if (empty($this->projectName) || empty($this->gitUsername) || empty($this->gitEmail)) {
            $this->printError('Missing arguments.');
            $this->printUsage();
            exit(1);
        }

        if (!preg_match('/^[a-zA-Z0-9\-\_\.]+$/', $this->projectName)) {
            $this->printError('Invalid project name. [a-zA-Z0-9-_.]');
            exit(1);
        }

        if (!preg_match('/^[a-zA-Z0-9\-\_\.]+$/', $this->gitUsername)) {
            $this->printError('Invalid git username. [a-zA-Z0-9-_.]');
            exit(1);
        }

        if (!filter_var($this->gitEmail, FILTER_VALIDATE_EMAIL)) {
            $this->printError('Invalid git email.');
            exit(1);
        }

        if (!in_array($this->dbType, self::DB_TYPES)) {
            $this->printError('Invalid database type. [mysql, postgres, mariadb]');
            exit(1);
        }
    }

    private function setOutputDir()
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        if (substr($this->outputDir, -1) !== DIRECTORY_SEPARATOR) {
            $this->outputDir .= DIRECTORY_SEPARATOR;
        }
    }

    public function createProject()
    {
        $this->createGitattributes();
        $this->createDockerCompose();
        $this->createReadme();
        $this->createDockerFiles();
        $this->createScripts();
        $this->printSuccess($this->projectName.' created.');
    }

    private function createGitattributes()
    {
        $content = [];
        $content[] = '*.css text eol=lf';
        $content[] = '*.htaccess text eol=lf';
        $content[] = '*.htm text eol=lf';
        $content[] = '*.html text eol=lf';
        $content[] = '*.js text eol=lf';
        $content[] = '*.json text eol=lf';
        $content[] = '*.map text eol=lf';
        $content[] = '*.md text eol=lf';
        $content[] = '*.php text eol=lf';
        $content[] = '*.profile text eol=lf';
        $content[] = '*.script text eol=lf';
        $content[] = '*.sh text eol=lf';
        $content[] = '*.svg text eol=lf';
        $content[] = '*.txt text eol=lf';
        $content[] = '*.xml text eol=lf';
        $content[] = '*.yml text eol=lf';

        file_put_contents($this->outputDir.'.gitattributes', implode(PHP_EOL, $content));
        chmod($this->outputDir.'.gitattributes', 0777);
    }

    private function createDockerCompose()
    {
        $content = [];
        $content[] = 'services:';
        $content[] = '    web:';
        $content[] = '        build: ./docker/web';
        $content[] = '        working_dir: /app';
        $content[] = '        user: application';
        $content[] = '        ports:';
        $content[] = '            - "80:80"';
        $content[] = '            - "443:443"';
        $content[] = '        volumes:';
        $content[] = '            - ./:/app';
        $content[] = '        tmpfs:';
        $content[] = '          - /tmp:mode=1777';
        $content[] = '        environment:';
        $content[] = '          - WEB_DOCUMENT_ROOT=/app/public';
        $content[] = '          - PHP_DISPLAY_ERRORS=1';
        $content[] = '          - PHP_MEMORY_LIMIT=512M';
        $content[] = '          - PHP_MAX_EXECUTION_TIME=300';
        $content[] = '          - PHP_POST_MAX_SIZE=200M';
        $content[] = '          - PHP_UPLOAD_MAX_FILESIZE=100M';
        if ($this->dbType === 'mysql') {
            $content[] = '    db:';
            $content[] = '        image: mysql:'.$this->mysqlVersion;
            $content[] = '        environment:';
            $content[] = '            MYSQL_ROOT_PASSWORD: root';
            $content[] = '            MYSQL_DATABASE: my_database';
            $content[] = '            MYSQL_USER: my_user';
            $content[] = '            MYSQL_PASSWORD: my_password';
            $content[] = '        volumes:';
            $content[] = '            - ./docker/mysql:/docker-entrypoint-initdb.d';
            $content[] = '            - ./docker/mysql/data:/var/lib/mysql';
            $content[] = '        ports:';
            $content[] = '            - "3306:3306"';
        } elseif ($this->dbType === 'postgres') {
            $content[] = '    db:';
            $content[] = '        image: postgres:'.$this->postgresVersion;
            $content[] = '        environment:';
            $content[] = '            POSTGRES_DB: my_database';
            $content[] = '            POSTGRES_USER: my_user';
            $content[] = '            POSTGRES_PASSWORD: my_password';
            $content[] = '        volumes:';
            $content[] = '            - ./docker/postgres:/docker-entrypoint-initdb.d';
            $content[] = '            - ./docker/postgres/data:/var/lib/postgresql/data';
            $content[] = '        ports:';
            $content[] = '            - "5432:5432"';
        } elseif ($this->dbType === 'mariadb') {
            $content[] = '    db:';
            $content[] = '        image: mariadb:'.$this->mariadbVersion;
            $content[] = '        environment:';
            $content[] = '            MYSQL_ROOT_PASSWORD: root';
            $content[] = '            MYSQL_DATABASE: my_database';
            $content[] = '            MYSQL_USER: my_user';
            $content[] = '            MYSQL_PASSWORD: my_password';
            $content[] = '        volumes:';
            $content[] = '            - ./docker/mariadb:/docker-entrypoint-initdb.d';
            $content[] = '            - ./docker/mariadb/data:/var/lib/mysql';
            $content[] = '        ports:';
            $content[] = '            - "3306:3306"';
        }

        file_put_contents($this->outputDir.'docker-compose.yml', implode(PHP_EOL, $content));
        chmod($this->outputDir.'docker-compose.yml', 0777);
    }

    private function createReadme()
    {
        $content = [];
        $content[] = '# '.$this->projectName;
        $content[] = '';
        $content[] = '### Setup Docker';
        $content[] = 'run `docker compose up` to build and run the container';
        $content[] = '';
        $content[] = '### Setup Symfony';
        $content[] = '- run `docker/bash.sh` to get into the container';
        $content[] = '';
        $content[] = '#### inside the container run';
        $content[] = '- `composer create-project symfony/skeleton:"6.*" apptemp` to create a new symfony project for symfony 6';
        $content[] = '- `composer create-project symfony/skeleton:"7.*" apptemp` to create a new symfony project for symfony 7';
        $content[] = '- `mv /app/apptemp/* /app/` to move the files from the temp folder to the root folder';
        $content[] = '- `find /app/apptemp/ -name ".*" ! -name . ! -name .. -exec mv {} /app/ \;` to move the hidden files from the temp folder to the root folder';
        $content[] = '- `rm -R /app/apptemp` to remove the temp folder';
        $content[] = '';
        $content[] = '#### mariadb|postgres|mysql setup';
        $content[] = '- run `echo "/docker/mariadb/" >> .gitignore` to ignore the mariadb folder';
        $content[] = '- run `echo "/docker/postgres/" >> .gitignore` to ignore the postgres folder';
        $content[] = '- run `echo "/docker/mysql/" >> .gitignore` to ignore the mysql folder';
        $content[] = '- run `echo "/.idea/" >> .gitignore` to ignore the idea folder';
        $content[] = '';
        $content[] = '#### inside the container setup symfony';
        $content[] = '- `composer require jbsnewmedia/symfony-web-pack` to install the webapp bundle';

        file_put_contents($this->outputDir.'README.md', implode(PHP_EOL, $content));
        chmod($this->outputDir.'README.md', 0777);
    }

    private function createDockerFiles()
    {
        $webDir = $this->outputDir.'docker'.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR;
        if (!is_dir($webDir)) {
            mkdir($webDir, 0777, true);
        }

        $content = [];
        $content[] = 'FROM webdevops/php-apache-dev:'.$this->phpVersion;
        $content[] = '';
        $content[] = '# Update and install';
        $content[] = 'RUN apt-get update && apt-get install -y';
        $content[] = '';
        $content[] = '#Nano';
        $content[] = 'RUN apt-get install -y nano';
        $content[] = '';
        $content[] = '#Keyring';
        $content[] = 'RUN mkdir -p /etc/apt/keyrings';
        $content[] = '';
        $content[] = '# Node.js';
        $content[] = 'RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg';
        $content[] = 'RUN NODE_MAJOR=18 && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list';
        $content[] = '';
        $content[] = '# Yarn';
        $content[] = 'RUN curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor |  tee /usr/share/keyrings/yarnkey.gpg >/dev/null 2>&1';
        $content[] = 'RUN echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian stable main" |  tee /etc/apt/sources.list.d/yarn.list >/dev/null 2>&1';
        $content[] = 'RUN apt-get update && apt-get install -y yarn';
        $content[] = '';
        $content[] = '# Symfony CLI';
        $content[] = 'RUN curl -1sLf \'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh\' | bash';
        $content[] = 'RUN apt-get update && apt-get install -y symfony-cli';
        $content[] = '';
        $content[] = '# Git config';
        $content[] = 'USER application';
        $content[] = 'RUN git config --global user.email "'.$this->gitEmail.'"';
        $content[] = 'RUN git config --global user.name "'.$this->gitUsername.'"';

        file_put_contents($webDir.'Dockerfile', implode(PHP_EOL, $content));
        chmod($webDir.'Dockerfile', 0777);
    }

    private function createScripts()
    {
        $this->createScript(
            'bash.bat',
            'docker exec --user=application -it -w /app '.$this->projectName.'-web-1 bash'
        );
        $this->createScript(
            'bash.sh',
            '#!/bin/bash'."\n".'docker exec --user=application -it -w /app '.$this->projectName.'-web-1 bash'
        );
        $this->createScript(
            'root.bat',
            'docker exec --user=root -it -w /app '.$this->projectName.'-web-1 bash'
        );
        $this->createScript(
            'root.sh',
            '#!/bin/bash'."\n".'docker exec --user=root -it -w /app '.$this->projectName.'-web-1 bash'
        );
    }

    private function createScript($filename, $content)
    {
        file_put_contents($this->outputDir.'docker'.DIRECTORY_SEPARATOR.$filename, $content);
        chmod($this->outputDir.'docker'.DIRECTORY_SEPARATOR.$filename, 0777);
    }

    private function printError($message)
    {
        echo self::COLORS['RED'].$message.self::COLORS['NONE'].self::NL;
    }

    private function printSuccess($message)
    {
        echo self::COLORS['GREEN'].$message.self::COLORS['NONE'].self::NL;
    }

    private function printUsage()
    {
        echo self::NL;
        echo 'Create a Symfony project with specific Git and optional version parameters.'.self::NL;
        echo self::NL;
        echo 'OPTIONS'.self::NL;
        echo '    -project_name     Name of the project.'.self::NL;
        echo '    -git_username     Git username.'.self::NL;
        echo '    -git_email        Git email.'.self::NL;
        echo '    -php_version      Optional. PHP version for the project (default: 8.3).'.self::NL;
        echo '    -mariadb_version  Optional. MariaDB version for the project (default: 11.4).'.self::NL;
        echo '    -postgres_version Optional. Postgress version for the project (default: 16.3).'.self::NL;
        echo '    -mysql_version    Optional. MySQL version for the project (default: 8.4).'.self::NL;
        echo '    -db-type          Optional. Database type for the project (default: mysql).'.self::NL;
        if ($this->isSH === true) {
            echo self::NL;
            echo 'USAGE'.self::NL;
            echo '    createSFProject.sh -project_name=<project-name> -git_username=<git-username> -git_email=<git-email> [-php_version=<php-version>] [-maria_version=<mariadb-version>] [-postgres_version=<postgres-version>] [-mysql_version=<mysql-version>] [-db-type=<db-type>]'.self::NL;
            echo self::NL;
            echo 'EXAMPLE'.self::NL;
            echo '    createSFProject.sh -project_name=myproject -git_username=myusername -git_email=myemail@mydomain.tld'.self::NL;
        } else {
            echo self::NL;
            echo 'USAGE'.self::NL;
            echo '    php project.php --project-name=<project-name> --git-username=<git-username> --git-email=<git-email> [--php-version=<php-version>] [--mariadb-version=<mariadb-version>] [--postgres-version=<postgres-version>] [--mysql-version=<mysql-version>] [--db-type=<db-type>]'.self::NL;
            echo self::NL;
            echo 'EXAMPLE'.self::NL;
            echo '    php project.php --project-name=my-project --git-username=username --git-email=username@domain.tld'.self::NL;
        }
    }
}

$options = getopt('', [
    'project-name:',
    'git-username:',
    'git-email:',
    'php-version::',
    'mysql-version::',
    'output-dir::',
    'is-sh::',
]);

foreach ($options as $key => $value) {
    $options[$key] = trim($value);
}

$projectSetup = new ProjectSetup($options);
$projectSetup->createProject();

?>