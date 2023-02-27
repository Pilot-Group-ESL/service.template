## Service Template

#### Get your own version
- Set up a new project repository using the template as the base
- In this example we will use "service.training-api"

#### Clone the new project
```sh
git clone git@github.com:pilot-group-esl/service.training-api.git
```

#### Rename the service in the docker-compose files
- Global replace "service-template-" to match your project name.
- Global replace "service_template_" to match your project name. 
- Global replace pilot-group-esl/service.template to your new service name
- Add your project PR URL to .github/pull_request_template.md
- You can now test and commit your new service.

#### Known issues
- Update to PHPUnit 10 once it is supported by Symfony (Running 9.6 at the moment)
- Test database variable does not get loaded from .env.test - hardcoded to config for now
- Migrations are disabled in the workflow as we do not have any yet. 
- Workflow uses `--no-scripts` as DATABASE_URL not present (investigate best practice)
- Clean up DB config to use env variables instead of hard coding. (wait-for-deps, .env)
- There are some outdated workflow commands that needs updating to newer version (hard deadline end of March)
- Logging is missing at the moment

#### FINAL STEPS
- Remove the above section from documentation
- Test and push your new service as below

### Requirements
```
docker >= v20
docker-compose >= v1.27 
```

### Basic usage

#### Clone the new project
```sh
git clone git@github.com:pilot-group-esl/service.template.git
```

#### Use Xdebug and change exposed ports
- Copy docker-compose.override.yml.dist to docker-compose.override.yml and change it to your requirements
- Using pcov is faster, only use xdebug when you are debugging code

#### Run the project for the first time
```sh
docker-compose pull
docker-compose build --no-cache
docker-compose up
docker exec -ti service-template-fpm composer install
```

#### Create files from dist
```sh
cp phpstan-src.neon.dist phpstan-src.neon
cp phpstan-tests.neon.dist phpstan-tests.neon
cp phpunit.xml.dist phpunit.xml
cp phpcs.xml.dist phpcs.xml.neon
cp infection.json.dist infection.json
```

#### Create database and run migrations
```sh
# Only needed if you do not have migrations
docker exec -ti service-template-fpm console doctrine:database:create

# Run for dev env
docker exec -ti service-template-fpm console doctrine:migrations:migrate
# Run for tests
docker exec -ti service-template-fpm console doctrine:migrations:migrate --env=test --no-interaction
```

At the moment you will need to repeat this if you rebuild the project or if you kill / remove your containers.


#### Run individual tests for the whole project
```sh
#### PHP Unit
# all phpunit tests
docker exec -ti service-template-fpm phpunit tests
# feature tests only
docker exec -ti service-template-fpm phpunit tests/Feature
# generate html code coverage 
docker exec -ti service-template-fpm phpunit tests --coverage-html var/coverage

#### PHP CS
# run checks
docker exec -ti service-template-fpm vendor/bin/phpcs src tests && echo "PASS"
#auto fix code style where possible
docker exec -ti service-template-fpm vendor/bin/phpcbf src tests

#### PHP STAN
docker exec -ti service-template-fpm vendor/bin/phpstan analyse \
              --level=max \
              --configuration=phpstan-src.neon \
              src

docker exec -ti service-template-fpm vendor/bin/phpstan analyse \
              --level=max \
              --configuration=phpstan-tests.neon \
              tests
              
### INFECTION 
docker exec -ti service-template-fpm vendor/bin/phpunit tests/Unit \
              --coverage-text \
              --coverage-xml=/tmp/coverage/coverage-xml \
              --log-junit=/tmp/coverage/junit.xml 
docker exec -ti service-template-fpm vendor/bin/infection \
            --min-covered-msi=100 -j$(nproc) \
            --no-progress \
            --coverage=/tmp/coverage \
            --skip-initial-tests \
            --only-covered \
            --ignore-msi-with-no-mutations
```

#### Run all tests for a suite

```sh
docker exec -ti service-template-fpm sh bin/test.sh
```


#### Rebuild the project images
```sh
docker-compose kill \
  && docker-compose rm  -f \
  && docker-compose build --no-cache \
  && docker-compose up --remove-orphans 
```