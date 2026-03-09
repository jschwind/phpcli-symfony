# PHPCLI-Symfony

Erstellt eine Docker-Umgebung für ein Symfony-Projekt mit integrierten Code-Quality-Tools.

## Voraussetzungen

- PHP >= 8.2
- Docker & Docker Compose
- Git
- Composer (lokal für einige Tools falls benötigt, aber primär via Docker)

## Installation

```shell
git clone https://github.com/jschwind/phpcli-symfony.git
cd phpcli-symfony
chmod +x createSFProject.sh updateSFProject.sh
```

Fügen Sie die Skripte zu Ihrem PATH hinzu oder erstellen Sie Symlinks, z. B. unter Linux:

```shell
sudo ln -s $(pwd)/createSFProject.sh /usr/local/bin/createSFProject
sudo ln -s $(pwd)/updateSFProject.sh /usr/local/bin/updateSFProject
```

## Verwendung

### createSFProject (Initiales Setup)

Sie können das Skript im **interaktiven Modus** (empfohlen) oder mit **Kommandozeilenoptionen** ausführen.

#### Interaktiver Modus

Führen Sie den Befehl einfach ohne Argumente aus:

```shell
createSFProject
```

Das Skript führt Sie durch das Setup und fragt nach:
- Projektname (Standard: Name des aktuellen Verzeichnisses)
- Git-Benutzername (Standard: aus git config)
- Git-E-Mail (Standard: aus git config)
- PHP-Version (Standard: `8.3`)
- Symfony-Version (Standard: `7.*`)
- Datenbank-Typ (`postgres`, `mariadb`, `mysql`, `firebird`)
- Code-Quality-Tools (`ecs`, `rector`, `phpstan`, `phpunit`)
- Ausgabeverzeichnis (Standard: aktuelles Verzeichnis)

#### Kommandozeilenoptionen

```shell
createSFProject [OPTIONEN]
```

#### Verfügbare Optionen
* `-project-name`: Name des Projekts.
* `-git-username`: Git-Benutzername.
* `-git-email`: Git-E-Mail.
* `-php-version`: PHP-Version (Standard: `8.3`).
* `-symfony-version`: Symfony-Version (Standard: `7.*`).
* `-db-type`: Datenbank-Typ (`postgres`, `mariadb`, `mysql`, `firebird`) (Standard: `postgres`).
* `-mariadb-version`: MariaDB-Version (Standard: `11.5`).
* `-postgres-version`: PostgreSQL-Version (Standard: `17.0`).
* `-mysql-version`: MySQL-Version (Standard: `9.1`).
* `-firebird-version`: Firebird-Version (Standard: `5.0`).
* `-code-quality`: Code-Quality-Tools aktivieren (`true`/`false`).
* `-tools`: Kommagetrennte Liste der Tools (`ecs,rector,phpstan,phpunit`).
* `-output-dir`: Zielverzeichnis für die Projektgenerierung.

### updateSFProject (Code-Quality-Tools aktualisieren)

Wenn Sie ein bestehendes Projekt haben und nur die Code-Quality-Tools hinzufügen oder aktualisieren möchten, verwenden Sie `updateSFProject`.

#### Interaktiver Modus

```shell
updateSFProject
```

Das Skript fragt nach:
- Projektverzeichnis (Standardmäßig das aktuelle Verzeichnis)
- PHP-Version (wird automatisch aus der `composer.json` erkannt, falls verfügbar)
- Symfony-Version (wird automatisch aus der `composer.json` erkannt, falls verfügbar)
- Hinzuzufügende Tools

#### Kommandozeilenoptionen

```shell
updateSFProject -php-version=8.4 -symfony-version=7.2 -tools=ecs,rector -output-dir=.
```

## Generierte Projektstruktur

Nach dem Ausführen von `createSFProject` finden Sie ein `docker/`-Verzeichnis in Ihrem Projekt, das Folgendes enthält:

- `bash.sh` / `bash.bat`: Den PHP-Container als Benutzer `application` betreten.
- `root.sh` / `root.bat`: Den PHP-Container als Benutzer `root` betreten.
- `docker-compose.yml`: Docker-Konfiguration.
- `web/Dockerfile`: Konfiguration der PHP-Umgebung.

## Integration der Code-Qualität

Falls aktiviert, richtet das Tool eine moderne Entwicklungsumgebung unter Verwendung des `bamarni/composer-bin-plugin` ein:

- **ECS (Easy Coding Standard)**: Prüfung des Kodierungsstils.
- **Rector**: Automatisierte Code-Upgrades und Refactoring.
- **PHPStan**: Statische Analyse.
- **PHPUnit**: Unit- und Funktionstests.

Alle Tools werden im Verzeichnis `vendor-bin/` installiert, um Abhängigkeitskonflikte mit Ihrem Hauptprojekt zu vermeiden. Composer-Skripte wie `composer ci`, `composer test` und `composer bin-rector` werden automatisch hinzugefügt und konfiguriert.

## Beispiele

**Interaktives Setup:**
```shell
createSFProject
```

**Nicht-interaktiv mit spezifischen Tools:**
```shell
createSFProject -project-name=my-app -php-version=8.4 -db-type=mariadb -code-quality=true -tools=ecs,phpstan,phpunit
```
