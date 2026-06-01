<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_array;
use function is_string;
use function strpos;

/**
 * Factory for creating the DefaultAuthenticationListener from configuration.
 */
class DefaultAuthenticationListenerFactory implements FactoryInterface
{
    /**
     * Create and return a DefaultAuthenticationListener.
     *
     * @param string             $requestedName
     * @param null|array         $options
     * @return DefaultAuthenticationListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $listener = new DefaultAuthenticationListener();

        $httpAdapter = $this->retrieveHttpAdapter($container);
        if ($httpAdapter) {
            $listener->attach($httpAdapter);
        }

        $authenticationTypes = $this->getAuthenticationTypes($container);
        if ($authenticationTypes) {
            $listener->addAuthenticationTypes($authenticationTypes);
        }

        $listener->setAuthMap($this->getAuthenticationMap($container));

        return $listener;
    }

    /**
     * Create and return a DefaultAuthenticationListener (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @return DefaultAuthenticationListener
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DefaultAuthenticationListener::class);
    }

    /**
     * @param ContainerInterface $services
     * @return false|HttpAdapter
     */
    protected function retrieveHttpAdapter(ContainerInterface $container)
    {
        // Allow applications to provide their own AuthHttpAdapter service; if none provided,
        // or no HTTP adapter configuration provided to api-tools-mvc-auth, we can stop early.

        $httpAdapter = $container->get('Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter');

        if ($httpAdapter === false) {
            return false;
        }

        // We must abort if no resolver was provided
        if (
            ! $httpAdapter->getBasicResolver()
            && ! $httpAdapter->getDigestResolver()
        ) {
            return false;
        }

        $authService = $container->get('authentication');

        return new HttpAdapter($httpAdapter, $authService);
    }

    /**
     * Retrieve custom authentication types
     *
     * @return array|false
     */
    protected function getAuthenticationTypes(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');
        if (
            ! isset($config['api-tools-mvc-auth']['authentication']['types'])
            || ! is_array($config['api-tools-mvc-auth']['authentication']['types'])
        ) {
            return false;
        }

        return $config['api-tools-mvc-auth']['authentication']['types'];
    }

    /**
     * @return array
     */
    protected function getAuthenticationMap(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');
        if (
            ! isset($config['api-tools-mvc-auth']['authentication']['map'])
            || ! is_array($config['api-tools-mvc-auth']['authentication']['map'])
        ) {
            return [];
        }

        return $config['api-tools-mvc-auth']['authentication']['map'];
    }
}
