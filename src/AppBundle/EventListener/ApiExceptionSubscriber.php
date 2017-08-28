<?php

namespace AppBundle\EventListener;

use AppBundle\Api\ResponseFactory;
use Psr\Log\LoggerInterface;
use AppBundle\Api\ApiProblem;
use AppBundle\Api\ApiProblemException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;


class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var boolean
     */
    private $debug;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * ApiExceptionSubscriber constructor.
     * @param $debug
     * @param LoggerInterface $logger
     * @param ResponseFactory $responseFactory
     */
    public function __construct($debug, LoggerInterface $logger, ResponseFactory $responseFactory)
    {
        $this->debug = $debug;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();

        $this->logException($e);

        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        // allow 500 errors to be thrown
        if ($this->debug && $statusCode >= 500) {
            return;
        }

        if ($e instanceof ApiProblemException) {
            $apiProblem = $e->getApiProblem();
        } else {
            $apiProblem = new ApiProblem(
                $statusCode
            );
            /*
             * If it's an HttpException message (e.g. for 404, 403),
             * we'll say as a rule that the exception message is safe
             * for the client. Otherwise, it could be some sensitive
             * low-level exception, which should *not* be exposed
             */
            if ($e instanceof HttpExceptionInterface) {
                $apiProblem->set('detail', $e->getMessage());
            }
        }

        $response = $this->responseFactory->createResponse($apiProblem);

        $event->setResponse($response);
    }

    /**
     * @param \Exception $exception
     */
    private function logException(\Exception $exception)
    {
        $isCritical = !$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500;
        $context = ['exception' => $exception];

        if ($isCritical) {
            $this->logger->critical($exception->getMessage(), $context);
        } else {
            $this->logger->error($exception->getMessage(), $context);
        }
    }
}