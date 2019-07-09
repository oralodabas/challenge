<?php


namespace App\EventListener;


use App\Exception\RepositoryException;
use App\Exception\ValidationException;
use App\Service\Tools\EmTransactionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{

    /**
     * @var EmTransactionService
     */
    private $transactionService;

    /**
     * ExceptionSubscriber constructor.
     * @param EmTransactionService $transactionService
     */
    public function __construct(EmTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * Handle the kernel exceptions.
     *
     * @param GetResponseForExceptionEvent $event
     * @throws \App\Exception\ServiceException
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        if ($this->transactionService->isTransactionActive()) {
            $this->transactionService->rollback();
        }

        $exception = $event->getException();
        $response = $this->getResponse($exception);

        if ($response->getStatusCode() == Response::HTTP_FORBIDDEN) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
        }

        $event->setResponse($response);
    }

    /**
     * Transform the exception messages.
     *
     * @param  \Exception $exception
     * @return JsonResponse
     */
    protected function getResponse(\Exception $exception)
    {
        if ($exception instanceof ValidationException) {
            $message = $exception->getErrors();
        } elseif ($exception instanceof RepositoryException) {
            $message = $exception->getException()->getMessage();
        } else {
            $message = $exception->getMessage();
        }

        $response = [
            'status' => 'error',
            'message' => $message,
            'data' => []
        ];

        if (getenv('APP_ENV') === 'dev') {
            $dbt = $exception->getTrace();
            $caller = isset($dbt[1]['function']) && isset($dbt[1]['class']) ? $dbt[1]['class'] . ':' . $dbt[1]['function'] : null;
            $response['code'] = $caller;
        }

        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = Response::HTTP_BAD_REQUEST;
        }

        return new JsonResponse($response, $statusCode);
    }
}