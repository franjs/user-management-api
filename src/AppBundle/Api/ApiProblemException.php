<?php

namespace AppBundle\Api;

use Symfony\Component\HttpKernel\Exception\HttpException;


class ApiProblemException extends HttpException
{
    /**
     * @var ApiProblem
     */
    private $apiProblem;

    /**
     * ApiProblemException constructor.
     * @param ApiProblem $apiProblem
     * @param null $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(
        ApiProblem $apiProblem,
        $message = null,
        \Exception $previous = null,
        array $headers = array(),
        $code = 0
    )
    {
        $this->apiProblem = $apiProblem;
        $statusCode = $apiProblem->getStatusCode();
        $message = $apiProblem->getTitle();

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    /**
     * @return ApiProblem
     */
    public function getApiProblem()
    {
        return $this->apiProblem;
    }
}