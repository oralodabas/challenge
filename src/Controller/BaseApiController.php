<?php


namespace App\Controller;


use App\EventListener\AvoidDoctrineProxySubscriber;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class BaseApiController extends AbstractController
{

    /**
     * @param $data
     * @return Response
     */
    private function response($data)
    {
        $serializer = SerializerBuilder::create()
            ->configureListeners(function (EventDispatcher $dispatcher) {
                $dispatcher->addSubscriber(new AvoidDoctrineProxySubscriber());
            });

        $status = $data['status_code'] ?? 200;

        if (isset($data['status_code'])) unset($data['status_code']);

        if (getenv('APP_ENV') != 'dev' && isset($data['code'])) {
            unset($data['code']);
        }

        $context = new SerializationContext();
        $context->setSerializeNull(true);
        $jsonContent = $serializer->build()->serialize($data, 'json', $context);

        $response = new Response();
        $response->setStatusCode($status);

        $response->setContent($jsonContent);

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    public function errorResponse($message = 'error', $statusCode = Response::HTTP_BAD_REQUEST)
    {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $caller = isset($dbt[1]['function']) ? $dbt[1]['class'] . ':' . $dbt[1]['function'] : null;

        $data = [
            'status' => 'error',
            'message' => $message,
            'data' => [],
            'code' => $caller,
            'status_code' => $statusCode,
        ];

        return $this->response($data);
    }

    public function successResponse($data = [], $message = 'success', $statusCode = Response::HTTP_OK)
    {
        $data = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode,
        ];

        return $this->response($data);
    }


    public function getRequestContentParams(Request $request)
    {

        if ($request->getMethod() == "GET") {
            $params = $request->query->all();

        } else {
            $params = json_decode($request->getContent(), true);

        }

        return $params;
    }


}