# Binioufous


#### To install first:

```
alias composer="php7.4 ~/composer.phar"

composer require symfony/webpack-encore-bundle

if problem with memory : php7.4 -d memory_limit=-1 ../composer.phar --profile install --no-dev --optimize-autoloader

npm-node10 install

npm-node10 install jquery popper.js bootstrap --save
npm-node10 install wavesurfer.js bootstrap-icons --save

rm -rf node_modules
npm install

composer require orm-fixtures --dev
composer require fzaninotto/faker
composer require cocur/slugify
```

#### To compile and launch locally:

* PHP Symfony server
```
php -S localhost:8000 -t public
```

* Webpack Encore (Assets)
```
yarn encore dev --watch
yarn encore production
```
