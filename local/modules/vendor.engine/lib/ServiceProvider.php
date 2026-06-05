<?php

declare(strict_types=1);

namespace Vendor\Engine;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\AutoWire\Binder;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Vendor\Engine\UseCase\GetExampleUseCase;
use Vendor\Engine\UseCase\ListExamplesUseCase;
use Bitrix\Main\DI\Exception\RegistrationException;
use Vendor\Engine\Internals\Repository\ExampleRepository;
use Vendor\Engine\Internals\Repository\ExampleRepositoryInterface;

/**
 * Регистрация зависимостей модуля в ServiceLocator и автовайринг
 * UseCase'ов в экшены контроллеров через AutoWire\Binder.
 *
 * Шаблон вертикального среза: Repository(Interface) → UseCase → (автовайринг) → Controller.
 */
final class ServiceProvider
{
    /**
     * @throws RegistrationException
     */
    public static function register(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }

        $locator = ServiceLocator::getInstance();

        if (!$locator->has(ExampleRepositoryInterface::class)) {
            $locator->addInstanceLazy(ExampleRepositoryInterface::class, [
                'className' => ExampleRepository::class,
            ]);
        }

        if (!$locator->has('vendor.engine.example.listUseCase')) {
            $locator->addInstanceLazy('vendor.engine.example.listUseCase', [
                'className'         => ListExamplesUseCase::class,
                'constructorParams' => static fn(): array => [
                    ServiceLocator::getInstance()->get(ExampleRepositoryInterface::class),
                ],
            ]);
        }

        if (!$locator->has('vendor.engine.example.getUseCase')) {
            $locator->addInstanceLazy('vendor.engine.example.getUseCase', [
                'className'         => GetExampleUseCase::class,
                'constructorParams' => static fn(): array => [
                    ServiceLocator::getInstance()->get(ExampleRepositoryInterface::class),
                ],
            ]);
        }

        Binder::registerGlobalAutoWiredParameter(
            new Parameter(
                ListExamplesUseCase::class,
                static fn(): ListExamplesUseCase => ServiceLocator::getInstance()->get('vendor.engine.example.listUseCase'),
            )
        );
        Binder::registerGlobalAutoWiredParameter(
            new Parameter(
                GetExampleUseCase::class,
                static fn(): GetExampleUseCase => ServiceLocator::getInstance()->get('vendor.engine.example.getUseCase'),
            )
        );

        $registered = true;
    }
}
