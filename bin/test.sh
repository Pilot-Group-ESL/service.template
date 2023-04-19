composer validate && echo "COMPOSER IS VALID" \
&& vendor/bin/phpcs src tests && echo "CODING STANDARDS PASS" \
&& vendor/bin/phpstan analyse \
  --level=max \
  --configuration=phpstan-src.neon \
  src \
&& vendor/bin/phpstan analyse \
  --level=max \
  --configuration=phpstan-tests.neon \
  tests \
&& export SYMFONY_DEPRECATIONS_HELPER="max[direct]=0&max[indirect]=999999" \
&& vendor/bin/phpunit tests/Application \
&& vendor/bin/phpunit tests/Integration \
&& vendor/bin/phpunit tests/Unit \
  --coverage-text \
  --coverage-xml=/tmp/coverage/coverage-xml \
  --log-junit=/tmp/coverage/junit.xml \
&& vendor/bin/infection \
  --min-covered-msi=100 -j$(nproc) \
  --no-progress \
  --coverage=/tmp/coverage \
  --skip-initial-tests \
  --only-covered \
  --ignore-msi-with-no-mutations
