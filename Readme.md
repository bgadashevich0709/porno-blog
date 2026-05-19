test

vendor/bin/doctrine-migrations diff

vendor/bin/doctrine-migrations migrate

composer db:fixtures


docker exec -it porno-php-1 sass --watch public/assets/css/style.scss:public/assets/build/style.css
