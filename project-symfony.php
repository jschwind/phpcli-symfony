<?php

/**
 * Create a Docker environment for a Symfony project.
 *
 * (c) 2025 Juergen Schwind <info@juergen-schwind.de>
 * GitHub: https://github.com/jschwind/phpcli-symfony
 *
 * MIT License
 *
 */

class ProjectSetup
{
    const COLORS = ['GREEN' => "\033[32m", 'RED' => "\033[31m", 'NONE' => "\033[0m",];
    const NL = "\n";
    const DB_TYPES = ['mysql', 'postgres', 'mariadb', 'firebird', 'none'];

    protected ?string $projectName;
    protected ?string $gitUsername;
    protected ?string $gitEmail;
    protected string $phpVersion;
    protected string $mysqlVersion;
    protected string $postgresVersion;
    protected string $mariadbVersion;
    protected string $firebirdVersion;
    protected string $dbType;
    protected string $symfonyVersion;
    protected string $outputDir;
    protected bool $isSH;
    protected bool $addCodeQuality;
    protected array $codeQualityTools = [];

    public function __construct($options)
    {
        // Check if we should run interactive mode.
        // We run it if no functional options are provided.
        // 'is-sh' and 'output-dir' are considered internal/auto-set.
        $functionalOptions = array_diff_key($options, ['is-sh' => 1, 'output-dir' => 1, 'code-quality' => 1, 'tools' => 1]);

        if (empty($functionalOptions)) {
            $this->setOptions($options); // Set defaults/internal first
            $this->interactiveSetup();
        } else {
            $this->setOptions($options);
        }

        $this->validateInputs();
        $this->setOutputDir();
    }

    protected function interactiveSetup()
    {
        if ($this->isSH) {
             // Re-open STDIN for interactive mode when running via shell script if necessary,
             // though usually not needed unless piped.
        }
        echo ProjectSetup::NL;
        echo "Welcome to Symfony Project Setup Tool".ProjectSetup::NL;
        echo "-------------------------------------".ProjectSetup::NL;

        $this->projectName = $this->ask("Project Name", basename(getcwd()));
        $this->gitUsername = $this->ask("Git Username", exec('git config user.name'));
        $this->gitEmail = $this->ask("Git Email", exec('git config user.email'));
        $this->phpVersion = $this->ask("PHP Version", "8.3");
        $this->symfonyVersion = $this->ask("Symfony Version", "7.*");

        echo ProjectSetup::NL."Database Configuration:".ProjectSetup::NL;
        $this->dbType = $this->askChoice("Database Type", ProjectSetup::DB_TYPES, "postgres");

        if ($this->dbType !== 'none') {
            $this->mariadbVersion = ($this->dbType === 'mariadb') ? $this->ask("MariaDB Version", "11.5") : "11.5";
            $this->postgresVersion = ($this->dbType === 'postgres') ? $this->ask("PostgreSQL Version", "17.0") : "17.0";
            $this->mysqlVersion = ($this->dbType === 'mysql') ? $this->ask("MySQL Version", "9.1") : "9.1";
            $this->firebirdVersion = ($this->dbType === 'firebird') ? $this->ask("Firebird Version", "5.0") : "5.0";
        }

        echo ProjectSetup::NL."Code Quality Tools:".ProjectSetup::NL;
        $this->addCodeQuality = $this->askConfirm("Add Code Quality Tools?", "yes");

        if ($this->addCodeQuality) {
            if ($this->askConfirm("Add ECS (Easy Coding Standard)?", "yes")) $this->codeQualityTools[] = 'ecs';
            if ($this->askConfirm("Add Rector?", "yes")) $this->codeQualityTools[] = 'rector';
            if ($this->askConfirm("Add PHPStan?", "yes")) $this->codeQualityTools[] = 'phpstan';
            if ($this->askConfirm("Add PHPUnit?", "yes")) $this->codeQualityTools[] = 'phpunit';
        }

        $this->outputDir = $this->ask("Output Directory", getcwd().DIRECTORY_SEPARATOR);
        if (substr($this->outputDir, -1) !== DIRECTORY_SEPARATOR) {
            $this->outputDir .= DIRECTORY_SEPARATOR;
        }
    }

    protected function ask($question, $default = null)
    {
        $prompt = $question;
        if ($default !== null) {
            $prompt .= " [$default]";
        }
        echo $prompt . ": ";
        $input = trim(fgets(STDIN));
        return $input === "" ? $default : $input;
    }

    protected function askChoice($question, array $choices, $default = null)
    {
        $prompt = $question . " (" . implode(", ", $choices) . ")";
        if ($default !== null) {
            $prompt .= " [$default]";
        }
        echo $prompt . ": ";
        $input = trim(fgets(STDIN));
        $input = $input === "" ? $default : $input;

        if (!in_array($input, $choices)) {
            echo "Invalid choice. Please choose from: " . implode(", ", $choices) . ProjectSetup::NL;
            return $this->askChoice($question, $choices, $default);
        }
        return $input;
    }

    protected function askConfirm($question, $default = "yes")
    {
        $prompt = $question . " (yes/no) [$default]";
        echo $prompt . ": ";
        $input = strtolower(trim(fgets(STDIN)));
        $input = $input === "" ? $default : $input;

        return in_array($input, ["y", "yes", "true", "1"]);
    }

    protected function log($message)
    {
        echo $message . ProjectSetup::NL;
    }

    protected function setOptions($options)
    {
        $this->projectName = (isset($options['project-name'])?$options['project-name']:basename(getcwd()));
        $this->gitUsername = (isset($options['git-username'])?$options['git-username']:null);
        $this->gitEmail = (isset($options['git-email'])?$options['git-email']:null);
        $this->phpVersion = (isset($options['php-version'])?$options['php-version']:'8.3');
        $this->postgresVersion = (isset($options['postgres-version'])?$options['postgres-version']:'17.0');
        $this->mysqlVersion = (isset($options['mysql-version'])?$options['mysql-version']:'9.1');
        $this->mariadbVersion = (isset($options['mariadb-version'])?$options['mariadb-version']:'11.5');
        $this->firebirdVersion = (isset($options['firebird-version'])?$options['firebird-version']:'5.0');
        $this->dbType = (isset($options['db-type'])?$options['db-type']:'postgres');
        $this->symfonyVersion = (isset($options['symfony-version'])?$options['symfony-version']:'7.*');
        $this->outputDir = (isset($options['output-dir'])?$options['output-dir']:getcwd().DIRECTORY_SEPARATOR);
        $this->isSH = (isset($options['is-sh']) && ($options['is-sh'] === 'true' || $options['is-sh'] === true || $options['is-sh'] === false));
        $this->addCodeQuality = (isset($options['code-quality']) && ($options['code-quality'] === 'true' || $options['code-quality'] === true || $options['code-quality'] === '1' || $options['code-quality'] === 1 || $options['code-quality'] === false));
        if (isset($options['tools'])) {
            $this->codeQualityTools = explode(',', $options['tools']);
        }
    }

    protected function validateInputs()
    {
        if (empty($this->projectName)) {
            $this->projectName = basename($this->outputDir);
        }

        if (empty($this->gitUsername)) {
            $this->gitUsername = exec('git config user.name');
        }

        if (empty($this->gitEmail)) {
            $this->gitEmail = exec('git config user.email');
        }

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

        if (!in_array($this->dbType, ProjectSetup::DB_TYPES)) {
            $this->printError('Invalid database type. [mysql, postgres, mariadb, firebird, none]');
            exit(1);
        }
    }

    protected function setOutputDir()
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
        $this->log("Creating project: " . $this->projectName);
        $this->createGitattributes();
        $this->createDockerCompose();
        $this->createReadme();
        $this->createDockerFiles();
        $this->createScripts();
        if ($this->addCodeQuality) {
            $this->setupCodeQuality();
        }
        $this->printSuccess("Project setup complete in: " . $this->outputDir);
    }

    protected function setupCodeQuality()
    {
        $this->log("Setting up code quality tools...");
        $targetDir = $this->outputDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // 1. Process composer.json
        $composerFile = $targetDir . 'composer.json';
        $composerData = [];
        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            if ($composerData === null) {
                $composerData = [];
            }
        }

        // Add bamarni/composer-bin-plugin if any tool is selected
        if (!empty($this->codeQualityTools)) {
            $composerData['require-dev']['bamarni/composer-bin-plugin'] = '^1.8';
            $composerData['config']['allow-plugins']['bamarni/composer-bin-plugin'] = true;
            $composerData['extra']['bamarni-bin']['bin-links'] = false;
            $composerData['extra']['bamarni-bin']['target-directory'] = 'vendor-bin';
            $composerData['extra']['bamarni-bin']['forward-command'] = true;
        }

        // Use internal skeleton composer.json for scripts
        $skeletonComposer = json_decode($this->getSkeletonFile('composer.json'), true);

        if (!isset($composerData['scripts'])) {
            $composerData['scripts'] = [];
        }

        foreach ($skeletonComposer['scripts'] as $key => $script) {
            $match = false;
            foreach ($this->codeQualityTools as $tool) {
                if (strpos($key, "bin-$tool") !== false) {
                    $match = true;
                    break;
                }
            }

            // Keep general test/ci scripts and add them if they don't exist
            if (in_array($key, ["test", "test-coverage", "test-full", "test-watch", "ci", "ci-fix", "ci-coverage"])) {
                $match = true;
            }

            if ($match) {
                // If it's a composite script (starts with @), filter its components
                if (is_array($script)) {
                    $filteredScript = [];
                    foreach ($script as $subScript) {
                        if (strpos($subScript, "@bin-") === 0) {
                            $subTool = str_replace(["@bin-", "-install", "-update", "-v", "-fix", "-process", "-no-coverage", "-coverage"], "", $subScript);
                            if (in_array($subTool, $this->codeQualityTools)) {
                                $filteredScript[] = $subScript;
                            }
                        } else {
                            $filteredScript[] = $subScript;
                        }
                    }
                    if (!empty($filteredScript)) {
                        $composerData['scripts'][$key] = $filteredScript;
                    }
                } else {
                    $composerData['scripts'][$key] = $script;
                }
            }
        }

        file_put_contents($composerFile, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 2. Process other config files
        $filesToCreate = [
            'phpstan' => ['phpstan-global.neon'],
            'phpunit' => ['phpunit-coverage.xml.dist', 'phpunit-no-coverage.xml.dist'],
            'rector' => ['rector.php'],
            'ecs' => ['.php-cs-fixer.dist.php']
        ];

        foreach ($filesToCreate as $tool => $files) {
            if (in_array($tool, $this->codeQualityTools)) {
                foreach ($files as $file) {
                    $content = $this->getSkeletonFile($file);
                    if ($content !== null) {
                        if ($file === 'rector.php') {
                            $phpVer = str_replace('.', '', $this->phpVersion);
                            $content = preg_replace('/php\d+: true/', 'php' . $phpVer . ': true', $content);
                        }
                        if (strpos($file, 'phpunit') === 0) {
                            $phpUnitVer = $this->getPhpUnitVersion($this->phpVersion);
                            $phpUnitVerNumeric = ltrim($phpUnitVer, '^');

                            // Adjust Schema version
                            $content = preg_replace('/https:\/\/schema\.phpunit\.de\/\d+\.\d+\/phpunit\.xsd/', 'https://schema.phpunit.de/' . $phpUnitVerNumeric . '/phpunit.xsd', $content);

                            if (version_compare($phpUnitVerNumeric, '10.0', '<')) {
                                // For PHPUnit 9.6: Remove new attributes
                                $content = preg_replace('/displayDetailsOnTestsThatTrigger\w+="true"/', '', $content);

                                // Replace <source> with <filter> for coverage (if present)
                                if (strpos($content, '<source') !== false) {
                                    $sourceBlock = '/<source.*?>(.*?)<\/source>/s';
                                    $filterBlock = "<filter>\n        <whitelist processUncoveredFilesFromWhitelist=\"true\">\n$1        </whitelist>\n    </filter>";
                                    $content = preg_replace($sourceBlock, $filterBlock, $content);
                                }
                            }
                        }
                        file_put_contents($targetDir . $file, $content);
                    }
                }
            }
        }

        // 3. Process vendor-bin folders
        foreach ($this->codeQualityTools as $tool) {
            $toolDir = 'vendor-bin' . DIRECTORY_SEPARATOR . $tool;
            $destDir = $targetDir . $toolDir;

            $content = $this->getSkeletonFile($toolDir . DIRECTORY_SEPARATOR . 'composer.json');
            if ($content !== null) {
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0777, true);
                }

                $sfVersionConstraint = $this->symfonyVersion;
                if (!preg_match('/[\^\~\>\<]/', $sfVersionConstraint) && strpos($sfVersionConstraint, '*') === false) {
                    $sfVersionConstraint = '^' . $sfVersionConstraint;
                }

                $content = preg_replace('/"symfony\/([^"]+)": "[^"]+"/', '"symfony/$1": "' . $sfVersionConstraint . '"', $content);

                if ($tool === 'phpunit' || $tool === 'rector') {
                     $phpUnitVer = $this->getPhpUnitVersion($this->phpVersion);
                     $content = preg_replace('/"phpunit\/phpunit": "\^11\.0"/', '"phpunit/phpunit": "' . $phpUnitVer . '"', $content);
                }

                if ($tool === 'rector') {
                    $content = preg_replace('/"php": "\d+\.\d+"/', '"php": "' . $this->phpVersion . '"', $content);
                }

                file_put_contents($destDir . DIRECTORY_SEPARATOR . 'composer.json', $content);
            }
        }
    }

    protected function getSkeletonFile($filename)
    {
        $skeleton = [
            'composer.json' => '{
  "require-dev": {
    "../vendor/bamarni/composer-bin-plugin": "^1.8"
  },
  "config": {
    "allow-plugins": {
      "bamarni/composer-bin-plugin": true
    }
  },
  "extra": {
    "bamarni-bin": {
      "bin-links": false,
      "target-directory": "vendor-bin",
      "forward-command": true
    }
  },
  "scripts": {
      "bin-ecs-install": [
          "composer bin ../vendor-bin/ecs install"
      ],
      "bin-ecs-update": [
          "composer bin ../vendor-bin/ecs update"
      ],
      "bin-ecs": ["vendor-bin/ecs/vendor/bin/php-cs-fixer check --allow-risky=yes"],
      "bin-ecs-fix": ["vendor-bin/ecs/vendor/bin/php-cs-fixer fix --allow-risky=yes"],
      "bin-ecs-v": ["vendor-bin/ecs/vendor/bin/php-cs-fixer -V"],

      "bin-phpstan-install": [
          "composer bin ../vendor-bin/phpstan install"
      ],
      "bin-phpstan-update": [
          "composer bin ../vendor-bin/phpstan update"
      ],
      "bin-phpstan": ["vendor-bin/phpstan/vendor/bin/phpstan analyse --configuration=phpstan-global.neon"],
      "bin-phpstan-v": ["vendor-bin/phpstan/vendor/bin/phpstan -V"],

      "bin-phpunit-install": [
          "composer bin ../vendor-bin/phpunit install"
      ],
      "bin-phpunit-update": [
          "composer bin ../vendor-bin/phpunit update"
      ],
      "bin-phpunit": ["vendor-bin/phpunit/vendor/bin/phpunit"],
      "bin-phpunit-no-coverage": ["vendor-bin/phpunit/vendor/bin/phpunit --configuration=phpunit-no-coverage.xml.dist"],
      "bin-phpunit-coverage": ["XDEBUG_MODE=coverage vendor-bin/phpunit/vendor/bin/phpunit --configuration=phpunit-coverage.xml.dist --coverage-html var/coverage"],
      "bin-phpunit-v": ["vendor-bin/phpunit/vendor/bin/phpunit --version"],

      "bin-rector-install": [
          "composer bin ../vendor-bin/rector install"
      ],
      "bin-rector-update": [
          "composer bin ../vendor-bin/rector update"
      ],
      "bin-rector": ["vendor-bin/rector/vendor/bin/rector --dry-run"],
      "bin-rector-process": ["vendor-bin/rector/vendor/bin/rector process"],
      "bin-rector-v": ["vendor-bin/rector/vendor/bin/rector -V"],

      "test": ["@bin-phpunit-no-coverage"],
      "test-coverage": ["@bin-phpunit-coverage"],
      "test-full": ["@bin-phpunit"],
      "test-watch": ["@bin-phpunit --testdox"],

      "ci": ["@bin-ecs", "@bin-rector", "@bin-phpstan", "@bin-phpunit-no-coverage"],
      "ci-fix": ["@bin-ecs-fix", "@bin-rector-process", "@bin-phpstan", "@bin-phpunit-no-coverage"],
      "ci-coverage": ["@bin-ecs", "@bin-rector", "@bin-phpstan", "@bin-phpunit-coverage"]
  }
}',
            'phpstan-global.neon' => '# Basis for core and plugin code quality
parameters:
    treatPhpDocTypesAsCertain: false
    paths:
        - src
    level: max',
            'phpunit-coverage.xml.dist' => '<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         displayDetailsOnTestsThatTriggerDeprecations="true"
         displayDetailsOnTestsThatTriggerErrors="true"
         displayDetailsOnTestsThatTriggerNotices="true"
         displayDetailsOnTestsThatTriggerWarnings="true"
>
    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>
</phpunit>',
            'phpunit-no-coverage.xml.dist' => '<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         displayDetailsOnTestsThatTriggerDeprecations="true"
         displayDetailsOnTestsThatTriggerErrors="true"
         displayDetailsOnTestsThatTriggerNotices="true"
         displayDetailsOnTestsThatTriggerWarnings="true"
>
    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>',
            'rector.php' => '<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . \'/src\',
    ])
    ->withPhpSets(php82: true)

    ->withComposerBased(
        symfony: true,
    )

    ->withSets([
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])

    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
        DeclareStrictTypesRector::class,
    ])
    ->withParallel();',
            '.php-cs-fixer.dist.php' => '<?php

$finder = (new PhpCsFixer\Finder())
    ->in(\'src\')
;

return (new PhpCsFixer\Config())
    ->setRules([
        \'@Symfony\' => true,
        \'strict_param\' => true,
        \'declare_strict_types\' => true,
        \'phpdoc_to_comment\' => false,
    ])
    ->setFinder($finder)
;',
            'vendor-bin/ecs/composer.json' => '{
    "require-dev": {
        "symplify/easy-coding-standard": "*",
        "friendsofphp/php-cs-fixer": "*"
    },
    "config": {
        "allow-plugins": {
            "bamarni/composer-bin-plugin": true,
            "phpstan/extension-installer": true
        }
    },
    "sort-packages": true
}',
            'vendor-bin/phpstan/composer.json' => '{
    "require-dev": {
        "phpstan/phpstan": "^2.0",
        "phpstan/extension-installer": "^1.0",
        "phpstan/phpstan-doctrine": "^2.0",
        "phpstan/phpstan-phpunit": "^2.0",
        "phpstan/phpstan-symfony": "^2.0"
    },
    "config": {
        "allow-plugins": {
            "phpstan/extension-installer": true,
            "bamarni/composer-bin-plugin": true
        }
    }
}',
            'vendor-bin/phpunit/composer.json' => '{
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "symfony/phpunit-bridge": "^7.*"
    },
    "config": {
        "allow-plugins": {
            "bamarni/composer-bin-plugin": true
        }
    }
}',
            'vendor-bin/rector/composer.json' => '{
    "require-dev": {
        "rector/rector": "^2.3"
    },
    "config": {
        "platform": {
            "php": "8.2"
        },
        "allow-plugins": {
            "bamarni/composer-bin-plugin": true
        }
    },
    "sort-packages": true
}',
        ];

        $key = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filename);
        // Map to forward slash for the internal array keys which are likely flat or use forward slash
        $key = str_replace(DIRECTORY_SEPARATOR, '/', $filename);

        return isset($skeleton[$key]) ? $skeleton[$key] : null;
    }

    private function getPhpUnitVersion($phpVersion)
    {
        if (version_compare($phpVersion, '8.3', '>=')) {
            return '^12.0';
        } elseif (version_compare($phpVersion, '8.2', '>=')) {
            return '^11.0';
        } elseif (version_compare($phpVersion, '8.1', '>=')) {
            return '^10.0';
        } elseif (version_compare($phpVersion, '8.0', '>=')) {
            return '^9.6';
        } else {
            return '^9.6';
        }
    }

    private function createGitattributes()
    {
        $this->log("Creating .gitattributes...");
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
        $this->log("Creating docker-compose.yml...");
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
        $content[] = '          - PHP_DISMOD=ioncube';
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
        } elseif ($this->dbType === 'firebird') {
            $content[] = '    db:';
            $content[] = '        image: jacobalberty/firebird:'.$this->firebirdVersion;
            $content[] = '        environment:';
            $content[] = '            ISC_PASSWORD: masterkey';
            $content[] = '            FIREBIRD_DATABASE: my_database.fdb';
            $content[] = '            TZ: Europe/Berlin';
            $content[] = '        volumes:';
            $content[] = '            - ./docker/firebird/data:/firebird/data';
            $content[] = '        ports:';
            $content[] = '            - "3050:3050"';
        } elseif ($this->dbType === 'none') {
            // No database service
        }

        file_put_contents($this->outputDir.'docker-compose.yml', implode(PHP_EOL, $content));
        chmod($this->outputDir.'docker-compose.yml', 0777);
    }

    private function createReadme()
    {
        $this->log("Creating README.md...");
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

        $content[] = '- `composer create-project symfony/skeleton:"'.$this->symfonyVersion.'" apptemp` to create the symfony project';
        $content[] = '- `mv /app/apptemp/* /app/` to move the files from the temp folder to the root folder';
        $content[] = '- `find /app/apptemp/ -name ".*" ! -name . ! -name .. -exec mv {} /app/ \;` to move the hidden files from the temp folder to the root folder';
        $content[] = '- `rm -R /app/apptemp` to remove the temp folder';
        $content[] = '';
        if ($this->dbType !== 'none') {
            $content[] = '#### mariadb|postgres|mysql setup';
            if ($this->dbType === 'mariadb') {
                $content[] = '- run `echo "/docker/mariadb/" >> .gitignore` to ignore the mariadb folder';
            } elseif ($this->dbType === 'postgres') {
                $content[] = '- run `echo "/docker/postgres/" >> .gitignore` to ignore the postgres folder';
            } elseif ($this->dbType === 'mysql') {
                $content[] = '- run `echo "/docker/mysql/" >> .gitignore` to ignore the mysql folder';
            } elseif ($this->dbType === 'firebird') {
                $content[] = '- run `echo "/docker/firebird/" >> .gitignore` to ignore the firebird folder';
            }
        }
        $content[] = '- run `echo "/.idea/" >> .gitignore` to ignore the idea folder';
        $content[] = '';
        $content[] = '#### inside the container setup symfony';
        $content[] = '- `composer require jbsnewmedia/symfony-web-pack` to install the webapp bundle';

        if ($this->dbType === 'firebird') {
            $content[] = '';
            $content[] = '### Doctrine Firebird';
            $content[] = '';
            $content[] = '#### config/packages/doctrine.yaml';
            $content[] = '```yaml';
            $content[] = 'doctrine:';
            $content[] = '    dbal:';
            $content[] = '        default_connection: default';
            $content[] = '        connections:';
            $content[] = '            default:';
            $content[] = '                driver_class: Satag\DoctrineFirebirdDriver\Driver\Firebird\Driver';
            $content[] = '                host: db';
            $content[] = '                port: 3050';
            $content[] = '                dbname: my_database.fdb';
            $content[] = '                user: sysdba';
            $content[] = '                password: masterkey';
            $content[] = '                charset: UTF-8';
            $content[] = '            profiling_collect_backtrace: \'%kernel.debug%\'';
            $content[] = '```';
        }

        file_put_contents($this->outputDir.'README.md', implode(PHP_EOL, $content));
        chmod($this->outputDir.'README.md', 0777);
    }

    private function createDockerFiles()
    {
        $this->log("Creating Dockerfiles...");
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
        if ($this->dbType === 'firebird') {
            $content[] = 'RUN apt-get install -y firebird-dev firebird3.0-utils && docker-php-source extract && git clone --branch v3.0.1 --depth 1 https://github.com/FirebirdSQL/php-firebird.git /usr/src/php/ext/interbase && docker-php-ext-install interbase';
            $content[] = '';
        }
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
        echo ProjectSetup::COLORS['RED'].$message.ProjectSetup::COLORS['NONE'].ProjectSetup::NL;
    }

    protected function printSuccess($message)
    {
        echo ProjectSetup::COLORS['GREEN'].$message.ProjectSetup::COLORS['NONE'].ProjectSetup::NL;
    }

    private function printUsage()
    {
        echo ProjectSetup::NL;
        echo 'Create a Symfony project with specific Git and optional version parameters.'.ProjectSetup::NL;
        echo ProjectSetup::NL;
        echo 'OPTIONS'.ProjectSetup::NL;
        echo '    -project_name     Name of the project.'.ProjectSetup::NL;
        echo '    -git_username     Git username.'.ProjectSetup::NL;
        echo '    -git_email        Git email.'.ProjectSetup::NL;
        echo '    -php_version      Optional. PHP version for the project (default: 8.3).'.ProjectSetup::NL;
        echo '    -mariadb_version  Optional. MariaDB version for the project (default: 11.5).'.ProjectSetup::NL;
        echo '    -postgres_version Optional. Postgress version for the project (default: 17.0).'.ProjectSetup::NL;
        echo '    -mysql_version    Optional. MySQL version for the project (default: 9.1).'.ProjectSetup::NL;
        echo '    -firebird_version Optional. Firebird version for the project (default: 5.0).'.ProjectSetup::NL;
        echo '    -db-type          Optional. Database type for the project (default: postgres). [mysql, postgres, mariadb, firebird, none]'.ProjectSetup::NL;
        echo '    -symfony-version  Optional. Symfony version for the project (default: 7).'.ProjectSetup::NL;
        if ($this->isSH === true) {
            echo ProjectSetup::NL;
            echo 'USAGE'.ProjectSetup::NL;
            echo '    createSFProject.sh -project-name=<project-name> -git-username=<git-username> -git-email=<git-email> [-php-version=<php-version>] [-maria-version=<mariadb-version>] [-postgres-version=<postgres-version>] [-mysql-version=<mysql-version>] [-firebird-version=<firebird-version>] [-db-type=<db-type>] [-symfony-version=<symfony-version>]'.ProjectSetup::NL;
            echo ProjectSetup::NL;
            echo 'EXAMPLE'.ProjectSetup::NL;
            echo '    createSFProject.sh -project-name=myproject -git-username=myusername -git-email=myemail@mydomain.tld'.ProjectSetup::NL;
        } else {
            echo ProjectSetup::NL;
            echo 'USAGE'.ProjectSetup::NL;
            echo '    php project.php --project-name=<project-name> --git-username=<git-username> --git-email=<git-email> [--php-version=<php-version>] [--mariadb-version=<mariadb-version>] [--postgres-version=<postgres-version>] [--mysql-version=<mysql-version>] [--firebird-version=<firebird-version>] [--db-type=<db-type>] [--symfony-version=<symfony-version>]'.ProjectSetup::NL;
            echo ProjectSetup::NL;
            echo 'EXAMPLE'.ProjectSetup::NL;
            echo '    php project.php --project-name=my-project --git-username=username --git-email=username@domain.tld'.ProjectSetup::NL;
        }
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', [
        'project-name:',
        'git-username:',
        'git-email:',
        'php-version::',
        'mysql-version::',
        'postgres-version::',
        'mariadb-version::',
        'firebird-version::',
        'db-type::',
        'symfony-version::',
        'output-dir::',
        'is-sh::',
        'code-quality::',
        'tools::',
    ]);

    foreach ($options as $key => $value) {
        if (is_string($value)) {
            $options[$key] = trim($value);
        }
    }

    $projectSetup = new ProjectSetup($options);
    $projectSetup->createProject();
}

?>