<?php

/**
 * Update Code Quality tools for an existing Symfony project.
 *
 * (c) 2025 Juergen Schwind <info@juergen-schwind.de>
 * GitHub: https://github.com/jschwind/phpcli-symfony
 *
 * MIT License
 *
 */

require_once __DIR__ . '/project-symfony.php';

class QualityUpdate extends ProjectSetup
{
    public function __construct($options)
    {
        $this->setOptions($options);
        // Set default output dir if not set before loading from composer
        if (!isset($this->outputDir) || empty($this->outputDir)) {
            $this->outputDir = getcwd() . DIRECTORY_SEPARATOR;
        }
        $this->loadVersionsFromComposer();

        // Check if we should run interactive mode for quality.
        $functionalOptions = array_diff_key($options, ['is-sh' => 1, 'output-dir' => 1, 'php-version' => 1, 'symfony-version' => 1, 'tools' => 1]);

        if (empty($functionalOptions)) {
            $this->interactiveQualitySetup();
        } else {
            // For non-interactive update, we still need to set some defaults if they are missing
            if (empty($this->phpVersion)) $this->phpVersion = '8.3';
            if (empty($this->symfonyVersion)) $this->symfonyVersion = '7.*';
            $this->addCodeQuality = true; // Ensure code quality is enabled for update
        }

        $this->setOutputDir();
    }

    protected function loadVersionsFromComposer()
    {
        $targetDir = $this->outputDir;
        $composerFile = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';

        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            if ($composerData !== null) {
                // PHP Version extract
                if (isset($composerData['require']['php'])) {
                    $phpVer = preg_replace('/[^0-9\.]/', '', $composerData['require']['php']);
                    if (!empty($phpVer)) {
                        $this->phpVersion = $phpVer;
                    }
                }
                // Symfony Version extract (from symfony/framework-bundle or other symfony/ package)
                $sfPackages = ['symfony/framework-bundle', 'symfony/console', 'symfony/http-kernel'];
                $foundSfVer = null;
                foreach ($sfPackages as $pkg) {
                    if (isset($composerData['require'][$pkg])) {
                        $sfVer = preg_replace('/[^0-9\.\*]/', '', $composerData['require'][$pkg]);
                        if (!empty($sfVer) && $sfVer !== '*') {
                            $foundSfVer = $sfVer;
                            break;
                        }
                    }
                }

                if ($foundSfVer === null) {
                    foreach ($composerData['require'] ?? [] as $pkg => $ver) {
                        if (strpos($pkg, 'symfony/') === 0) {
                            $sfVer = preg_replace('/[^0-9\.\*]/', '', $ver);
                            if (!empty($sfVer) && $sfVer !== '*') {
                                $foundSfVer = $sfVer;
                                break;
                            }
                        }
                    }
                }

                if ($foundSfVer !== null) {
                    $this->symfonyVersion = $foundSfVer;
                }
            }
        }
    }

    protected function validateInputs()
    {
        // Skip validation for update tool
    }

    private function interactiveQualitySetup()
    {
        echo ProjectSetup::NL;
        echo "Welcome to Code Quality Update Tool".ProjectSetup::NL;
        echo "-----------------------------------".ProjectSetup::NL;

        $this->outputDir = $this->ask("Project Directory", $this->outputDir);
        if (substr($this->outputDir, -1) !== DIRECTORY_SEPARATOR) {
            $this->outputDir .= DIRECTORY_SEPARATOR;
        }
        $this->loadVersionsFromComposer();

        $this->phpVersion = $this->ask("PHP Version", $this->phpVersion ?? "8.3");
        $this->symfonyVersion = $this->ask("Symfony Version", $this->symfonyVersion ?? "7.*");

        echo ProjectSetup::NL."Code Quality Tools:".ProjectSetup::NL;
        if ($this->askConfirm("Add ECS (Easy Coding Standard)?", "yes")) $this->codeQualityTools[] = 'ecs';
        if ($this->askConfirm("Add Rector?", "yes")) $this->codeQualityTools[] = 'rector';
        if ($this->askConfirm("Add PHPStan?", "yes")) $this->codeQualityTools[] = 'phpstan';
        if ($this->askConfirm("Add PHPUnit?", "yes")) $this->codeQualityTools[] = 'phpunit';

        $this->addCodeQuality = true; // For setupCodeQuality
    }

    public function updateQuality()
    {
        if (empty($this->codeQualityTools)) {
            $this->log("No tools selected. Nothing to do.");
            return;
        }
        
        $this->log("Updating code quality tools in: " . $this->outputDir);
        $this->setupCodeQuality();
        $this->printSuccess("Code quality setup complete in: " . $this->outputDir);
    }
}

// Check if we are running as a standalone script or being included.
// If we are the main script, we run the QualityUpdate logic.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', [
        'php-version::',
        'symfony-version::',
        'output-dir::',
        'is-sh::',
        'tools::',
    ]);

    foreach ($options as $key => $value) {
        if (is_string($value)) {
            $options[$key] = trim($value);
        }
    }

    $qualityUpdate = new QualityUpdate($options);
    $qualityUpdate->updateQuality();
}
