<?php

namespace Ekyna\Component\Payum\Monetico;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Payum\Core\GatewayFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class MoneticoGatewayFactory
 * @package Ekyna\Component\Payum\Monetico
 * @author  Etienne Dauvergne <contact@ekyna.com>
 */
class MoneticoGatewayFactory extends GatewayFactory
{
    /**
     * Builds a new factory.
     *
     * @param array                   $defaultConfig
     * @param GatewayFactoryInterface $coreGatewayFactory
     *
     * @return MoneticoGatewayFactory
     */
    public static function build(array $defaultConfig, GatewayFactoryInterface $coreGatewayFactory = null)
    {
        return new static($defaultConfig, $coreGatewayFactory);
    }

    /**
     * @inheritDoc
     */
    protected function populateConfig(ArrayObject $config)
    {
        $logger = new Logger('monetico_debug');
        $logger->pushHandler(new StreamHandler('/home/www/lescuyer-pp/public_html/current/var/log/moneticodebug.log', Logger::DEBUG));

        $responseAction = new Action\Api\PaymentResponseAction();
        $responseAction->setLogger($logger);

        $config->defaults([
            'payum.factory_name'  => 'monetico',
            'payum.factory_title' => 'Monetico',

            'payum.template.api_request' => '@EkynaPayumMonetico/api_request.html.twig',

            'payum.action.capture' => new Action\CaptureAction(),
            'payum.action.notify'  => new Action\NotifyAction(),
            'payum.action.status'  => new Action\StatusAction(),
            'payum.action.sync'    => new Action\SyncAction(),

            'payum.action.api.payment_response' => $responseAction,
            'payum.action.api.payment_form'     => function (ArrayObject $config) use ($logger) {
                    $formAction = new Action\Api\PaymentFormAction($config['payum.template.api_request']);
                    $formAction->setLogger($logger);
                return $formAction;
            },
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'mode'    => null,
                'tpe'     => null,
                'key'     => null,
                'company' => null,
                'debug'   => false,
            ];

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['mode', 'tpe', 'key', 'company', 'tpe_type'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $api = new Api\Api();

                $api->setConfig([
                    'mode'    => $config['mode'],
                    'tpe'     => $config['tpe'],
                    'key'     => $config['key'],
                    'company' => $config['company'],
                    'debug'   => $config['debug'],
                    'test_email'   => $config['test_email'],
                    'tpe_type'   => $config['tpe_type'],
                ]);

                return $api;
            };
        }

        $config['payum.paths'] = array_replace([
            'EkynaPayumMonetico' => __DIR__ . '/Resources/views',
        ], $config['payum.paths'] ?: []);
    }
}
