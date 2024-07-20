<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ReceivedConsumerService;
use ZBateson\MailMimeParser\Message\Factory\PartStreamContainerFactory;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;

return [
    LoggerInterface::class => DI\autowire(NullLogger::class),

    // only affects reading part content, not for instance decoding mime encoded
    // header parts
    'throwExceptionReadingPartContentFromUnsupportedCharsets' => false,

    'fromDomainConsumerService' => DI\autowire(DomainConsumerService::class)
        ->constructorParameter('partName', 'from'),
    'byDomainConsumerService' => DI\autowire(DomainConsumerService::class)
        ->constructorParameter('partName', 'by'),
    'viaGenericReceivedConsumerService' => DI\autowire(GenericReceivedConsumerService::class)
        ->constructorParameter('partName', 'via'),
    'withGenericReceivedConsumerService' => DI\autowire(GenericReceivedConsumerService::class)
        ->constructorParameter('partName', 'with'),
    'idGenericReceivedConsumerService' => DI\autowire(GenericReceivedConsumerService::class)
        ->constructorParameter('partName', 'id'),
    'forGenericReceivedConsumerService' => DI\autowire(GenericReceivedConsumerService::class)
        ->constructorParameter('partName', 'for'),
    ReceivedConsumerService::class => DI\autowire()
        ->constructor(
            fromDomainConsumerService: DI\get('fromDomainConsumerService'),
            byDomainConsumerService: DI\get('byDomainConsumerService'),
            viaGenericReceivedConsumerService: DI\get('viaGenericReceivedConsumerService'),
            withGenericReceivedConsumerService: DI\get('withGenericReceivedConsumerService'),
            idGenericReceivedConsumerService: DI\get('idGenericReceivedConsumerService'),
            forGenericReceivedConsumerService: DI\get('forGenericReceivedConsumerService')
        ),
    PartStreamContainer::class => DI\autowire()
        ->constructor(
            throwExceptionReadingPartContentFromUnsupportedCharsets: DI\get('throwExceptionReadingPartContentFromUnsupportedCharsets')
        ),
    PartStreamContainerFactory::class => DI\autowire()
        ->constructor(
            throwExceptionReadingPartContentFromUnsupportedCharsets: DI\get('throwExceptionReadingPartContentFromUnsupportedCharsets')
        ),
    ParserPartStreamContainerFactory::class => DI\autowire()
        ->constructor(
            throwExceptionReadingPartContentFromUnsupportedCharsets: DI\get('throwExceptionReadingPartContentFromUnsupportedCharsets')
        ),
    StreamFactory::class => DI\autowire()
        ->constructor(
            throwExceptionReadingPartContentFromUnsupportedCharsets: DI\get('throwExceptionReadingPartContentFromUnsupportedCharsets')
        ),
];
