<?php

namespace AppBundle\Api;

use Symfony\Component\HttpFoundation\JsonResponse;


class ResponseFactory
{
    /**
     * @param ApiProblem $apiProblem
     * @return JsonResponse
     */
    public function createResponse(ApiProblem $apiProblem)
    {
        $response = new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode()
        );

        $response->headers->set('Content-Type', 'application/problem+json');

        return $response;
    }
}