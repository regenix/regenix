## Установка

> Regenix легок в установке, вам лишь необходимо скопировать исходники фреймворка
> в корневую директорию вашего веб-сервера.

### Требования

1. Веб Сервер (nginx, apache или другой). Для apache mod_rewrite должен быть установлен.
2. PHP 5.3.2+ (также поддерживаются 5.4+, 5.5+ версии).
3. GD расширение (для некоторых функций)
4. Кеширование: APC or XCache (для большей производительности)

> **ВНИМАНИЕ**: Не устанавливайте eAccelerator т.к. это расширение вырезает любые комментарии из кода,
> а они используются для эмуляции аннотаций в PHP. Однако вы все же можете установить это расширение,
> но тогда вы должны отключить опцию оптимизации в конфигурации eAccelerator.

### Лицензия

Regenix лицензирован под Apache License 2.0. Это означает что вы можете свободно модифицировать,
распространять и публиковать исходные коды при условии что информация о копирайтах останется не тронутой.
Вы также свободно можете включать Regenix в любые комерческие или закрытые проекты.

### Загрузка Regenix

На данный момент есть только один способ получить Regenix - это склонировать все исходники фреймворка и начать
работать. Наш репозитарий исходников находится на Github: <https://github.com/dim-s/regenix>. Для того чтобы
склонировать исходники установите git и запустите следующие команды в git-bash:

    cd <root_of_server>
    git clone git@github.com:dim-s/regenix.git ./
    git submodule init
    git submodule update

И это все. После этого появится директория для ваши будущих приложений. По-умолчанию
эта директория располагается в корне и называется `apps`.

### Права доступа

Regenix использует `log` и `tmp` директории для различных операций. Эти директории должны
имет доступ для чтения и записи, все остальные директории в корне вебсервер - только чтение.

### Установка

Regenix не требует специальных действий для установки. Вам необходимо создать директорию вашего проекта
в папке `apps/` и добавить некоторые конфигурационные файлы. И это все.

---

## Apache конфигурация

Далее мы рассмотрим ряд примеров, с помощью которых вы сможете настроить
свой веб сервер. Чтобы настроить apache сервер вам необходимо создать
`.htaccess` файл в корневой директории. Ниже показана типичная конфигурация
для данного сервера:

    Options +FollowSymlinks -Indexes

    php_flag display_errors on
    php_value error_reporting E_ALL
    
    AddDefaultCharset utf-8
    
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteBase /
    
        # MODULES
        RewriteRule ^assets/(.*)$ - [L]
    
        # MODULES
        RewriteRule ^modules/([a-z0-9-_A-Z\~\.]+)/assets/(.*)$ - [L]
    
        # PUBLIC
        RewriteRule ^public/([a-z0-9-_A-Z\~\.]+)/(.*)$ - [L]
    
        # ASSETS
        RewriteRule ^apps/([a-z0-9-_A-Z\~\.]+)/assets/(.*)$ - [L]
    
        # APPS
        RewriteRule ^(.*)$ index.php?/$1 [L]
    </IfModule>


## Ngnix + FastCGI конфигурация

Пример для настройки nginx сервера:

    server {
        listen       80;
        server_name  127.0.0.1;
        charset utf8;

        root <root_of_your_web_server>;

        # static content
        location /public/ {
            autoindex off;
        }

        location /assets/ {
            autoindex off;
        }

        location ~ ^/apps/.*/assets/ {
            autoindex off;
        }

        location ~ ^/modules/.*/assets/ {
            autoindex off;
        }

        # dynamic content
        location / {
            fastcgi_pass  127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root/index.php;
            include       fastcgi_params;
        }
    }
    
> Также, вам необходио запустить cgi-сервер на _9000_ порту для обработки php скриптов

---

### Заключение

В общем, если вы хотите настроить какой-то другой веб сервер, вам необходимо обеспечить
следующее:

- Переправлять все динамические запросы на `<root>/index.php` скрипт.
- Переправлять все запросы для статики на директории: `/public/`, `/assets/`, `/modules/.*/assets/` and `/apps/.*/assets`.

Это все что нужно.

---

## Нестандартная конфигурация

Regenix имеет несколько директорий в корне:

- `framework` - папка исходников фреймворка
- `modules` - папка для модулей
- `apps` - папка с вашими приложениями

Также в корне есть некоторые файлы:

- `index.php` - основной файл для подключения фреймворка и обработки запросов.
- `.htaccess` - apache конфиг файл (если вы используете apache сервер)
- `regenix`, `regenix.bat` - консольные файлы для window и unix.

Однако, вы можете изменить расположение ядра фреймворка и приложений. Для этого
вам необходимо изменить кое-что в `index.php`. Далее мы рассмотрим пример типичного `index` файла:

    <?php 
    
    use regenix\Regenix;

    // require the framework
    require 'framework/include.php';
    
    // init and require applications
    Regenix::initWeb(__DIR__);
    
Здесь мы можем поменять путь до файла подключения фреймворка в строчке:

    require 'other/path/to/framework/include.php'

И это будет работать! Также, вы можете изменить путь к корневой папке в строке:

    Regenix::initWeb('other/path/of/root');

И это тоже будет работать.
