## Getting Started

1. Строим наши образы `docker-compose build` (в случае необходимости).
2. Запускаем наши контейнеры `docker-compose up -d`.
3. Заходим в наш контейнер `docker exec -it php_freedom bash`.
Команды далее выполняем внутри нашего контейнера.
4. Получить курсы валют  `bin/console app:cu-ra 30/07/2024 USD`
где дата должна быть указана в формате `DD/MM/YYY`, а валюта для получения 
курса задана в формате как `USD` , в таком же формате можем указать 
базовую валюту третьим параметром как `bin/console app:cu-ra 30/07/2024 USD GBP`
5. Запустить фоновый процесс получения валют за последние 180 дней, начиная с сегодня
 `bin/console app:fe-ex-ra`
6. Запустить worker  вручную `php bin/console messenger:consume async -vv`



Для отладки и работы Xdebug запускаем наши команды с указанием переменных окружения как:

`XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" bin/console app:fe-ex-ra`
