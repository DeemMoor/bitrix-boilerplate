# vendor.engine

Эталонный модуль болванки. Задаёт принятую в проекте слоёную архитектуру и служит
шаблоном для новых фич.

## Архитектура (вертикальный срез)

```
Controller  ──>  UseCase  ──>  Repository(Interface)  ──>  DTO/ReadModel  ──>  Presenter
                                   (Internals/)
```

- **Controller** (`lib/Controller`) — HTTP-слой, экшены описаны OpenAPI:
  атрибутами `#[OA\...]` (предпочтительно) или аннотациями `@OA\...`.
- **UseCase** (`lib/UseCase`) — прикладная логика, одна операция = один use case.
- **Repository** (`lib/Internals/Repository`) — доступ к данным, возвращает DTO,
  а не «голые» массивы ORM. Скрыт в `Internals/`, наружу торчит только интерфейс.
- **DTO/ReadModel** (`lib/DTO`) — неизменяемые модели данных, схемы OpenAPI на них.
- **Presenter** (`lib/Presenter`) — формирование ответа из ReadModel.
- **ServiceProvider** (`lib/ServiceProvider.php`) — регистрация зависимостей в
  `ServiceLocator` и автовайринг UseCase в экшены через `AutoWire\Binder`.

Каркас, который остаётся в любом проекте: `BaseController`, `ApiDocController`,
`OpenApi/ApiSpec` (глобальные метаданные), `ServiceProvider`, `Provider/Params/ModuleParams`,
`Internals/Exception/*`, `Command/GenerateApiDocCommand`, `include.php`, `routes.php`, `install/`.

## ⚠️ Образец `Example` — удалить в продуктовом репозитории

Сущность **Example** (и команда **Ping**) — это демонстрационный срез: показывает,
как строить фичу от роутинга до БД. Бизнес-логики в нём нет, в реальном проекте его
удаляют (или копируют-переименовывают под свою сущность: `Example*` → `Order*` и т.п.).

> **Автоматически:** из корня проекта запустите `php scripts/strip-demo.php` — он
> удалит файлы ниже, почистит структурные файлы до пустого каркаса и оставит
> слои-папки с `.gitkeep`. Дальше — что именно он делает (на случай ручной чистки).

### Что удалить

```
lib/Controller/ExampleController.php
lib/Controller/TestController.php
lib/UseCase/ListExamplesUseCase.php
lib/UseCase/GetExampleUseCase.php
lib/DTO/ExampleReadModel.php
lib/Presenter/ExamplePresenter.php
lib/Internals/Repository/ExampleRepository.php
lib/Internals/Repository/ExampleRepositoryInterface.php
lib/Entity/ExampleTable.php
lib/Command/PingCommand.php
```

### Что поправить

- `routes.php` — убрать маршруты `/api/example`, `/api/example/{id}`, `/api/test`
  (оставить `/api/doc`).
- `lib/ServiceProvider.php` — убрать регистрацию `ExampleRepository*` и use case'ов
  Example в `register()`.
- `install/index.php` — убрать `installDB()/uninstallDB()` для таблицы
  `vendor_engine_example` (и саму таблицу удалить из БД).
- `lib/OpenApi/ApiSpec.php` — убрать `#[OA\Tag]` для `Example` и `Test`.
- корневой `console` — убрать регистрацию `PingCommand` (вызов `addCommand` и импорт).

После чистки остаётся пустой, но рабочий каркас: слои-папки, базовые контроллеры,
DI-механизм и генерация OpenAPI.
