## create a docker for a symfony project

## Installation
```shell
git clone https://github.com/jschwind/phpcli-symfony.git
cd phpcli-symfony
chmod +x createSFProject.sh
```
createSFProject.sh to PATH Variable or create a link, e.g. Arch/Manjaro Linux: ~/.bashrc
```shell
sudo ln -s $(pwd)/createSFProject.sh /usr/local/bin
```

### syntax
`createSFProject.sh -project_name=myproject -git_username=myusername -git_email=myemail -php_version=8.2 -mariadb_version=10.4` 
