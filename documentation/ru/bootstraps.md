# Bootstrap

Regenix поддерживает бутстрапы в рамках приложения и глобально. Regenix это
мульти-проектный фреймворк и следовательно классы бутстрапов могут быть двух
типов - для приложения и глобальные.

Глобальный бутстрап перехватывает глобальные события, бутстрап приложения -
события приложения. Что такое бутстрап в Regenix? Это класс, который унаследован
от абстрактного бутстрап класса, который имеет несколько переопределенных
методов для отлова событий.

---

## Бутстрап приложения

Для создания бутстрапа приложения вам нужно создать новый класс унаследованный
от `regenix\core\AbstractBootstrap` класса. Внутри своего класса вы можете
переопределить некоторые методы, такие как `onStart`, `onEnvironment` и `onTest`.

    namespace {
  
        use regenix\AbstractBootstrap;
    
        class Bootstrap extends AbstractBootstrap {
    
            public function onStart(){
                // переопределенный метод ...
            }
            
        }
    }

> **ВАЖНО**: Класс вашего бутстрапа должен располагаться
> по адресу `<app>/src/Bootstrap.php` и называться `Bootstrap`.

Далее мы рассмотрим всем методы, которые можно переопределить.

---

###### onStart ######

Этот метод вызывается после загрузки приложения, но перед
отсылкой http-данных в браузер.

    public function onStart(){
        // ... что-нибудь, для примера объявления DI правил.
        // не подключайте здесь php файлы и библиотеки
    }
    

> **ВАЖНО**: Не подключайте любые php файлы или библиотеки в этом методы,
> т.к. это может быть причиной падения производительности. Дайте Regenix
> самому загружать все php файлы и библиотек только когда это нужно.


###### onEnvironment ######

Этот метод вызывается когда фреймворк пытается задать режим работы
вашему приложения. Режим может переключать в главном конфигурационном файле,
но иногда вам необходимо задавать его динамически. Для этого вы можете
переопределить `onEnvironment` метод, выглядит это так:

    public function onEnvironment(&$env){
        // ... здесь вы можете изменить $env динамически
        if ( ... ){
            $env = 'dev';
        }
    }

В этом методе, для примера, вы можете изменить режим, который зависит от хоста
или от чего-то еще.
    

###### onTest ######

Этот метод вызывается когда вы запускается на выполнение тесты вашего приложения.
Не имеет значения - где вы запускаете ваши тесты - в браузере или в CLI.
Этот метод будет вызываться всегда.

Однако, этот метод необходим для определения порядка запуска тестов.

    public function onTest(array &$tests){
        $tests = array(
            new tests\MyFirstTest(),
            new tests\MySecondTest(),
            ...
        );
    }

Массив экземпляров тестов будет передан в этот метод. Если вам необходимо
отсортировать их, вы должно создать новый массив вручную как показано выше.

---

## Глобальный бутстрап

Иногда вам необходимо глобально перехватывать события всех ваших приложений.
Для этого существуют глобальный бутстрап - класс, который унаследован от
`regenix\core\AbstractGlobalBootstrap`.

Глобальный бутстрап имеет несколько методов для переопределения:

1. `onException(\Exception $e)` - вызывается когда поднимается какое-то исключение
2. `onError(array $error)` - когда php выводит ошибку (не исключение!).
3. `onBeforeRegisterApps(File &$pathToApps)` - before regenix finds apps in the `apps` path. 
4. `onAfterRegisterApps(&$apps)` - after regenix finds apps
5. `onBeforeRegisterCurrentApp(Application $app)` - before regenix registers a application at the current request
6. `onAfterRegisterCurrentApp(Application $app)` - after the registration of the current application
7. `onBeforeRequest(Request $request)` - before regenix tries to render a page.
8. `onAfterRequest(Request $request)` - after a request, but before to render a page
9. `onFinallyRequest(Request $request)` - after render a page


