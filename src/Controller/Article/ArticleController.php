<?php

namespace App\Controller\Article;


use App\Controller\BaseApiController;
use App\Service\Article\ArticleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ArticleController extends BaseApiController
{

    /**
     * @var ArticleService
     */
    private $service;

    public function __construct(ArticleService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return array|Response
     * @Route("/document",name="document",methods={"GET"})
     */
    public function getDocumentAction(Request $request)
    {
        try {
            $params = $this->getRequestContentParams($request);

            $result = $this->service->getDocument($params);

            return $this->successResponse($result);

        } catch (\Exception $e) {

            return $this->errorResponse($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }
    }
}