<?php

namespace AppBundle\Controller;

use AppBundle\Api\ApiProblem;
use AppBundle\Api\ApiProblemException;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class BaseController extends Controller
{
    /**
     * @param Request $request
     * @param FormInterface $form
     * @return FormInterface
     */
    protected function processForm(Request $request, FormInterface $form)
    {
        $data = $this->decodeRequestBody($request);

        $clearMissing = $request->getMethod() != 'PATCH';

        $form->submit($data, $clearMissing);

        return $form;
    }

    protected function decodeRequestBody(Request $request)
    {
        // allow for a possibly empty body
        if (!$request->getContent()) {
            return array();
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            $apiProblem = new ApiProblem(400, ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT);
            throw new ApiProblemException($apiProblem);
        }

        return $data;
    }

    /**
     * @param $data
     * @param string $format
     * @param string $group
     * @return mixed|string
     */
    protected function serialize($data, $format = 'json', $group = null)
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);
        $context->enableMaxDepthChecks();

        if (!empty($group)) {
            $context->setGroups([$group]);
        }

        return $this->get('jms_serializer')->serialize($data, $format, $context);
    }

    /**
     * @param $data
     * @param string $to
     * @param string $format
     * @return array|\JMS\Serializer\mixed|object
     */
    protected function deserialize($data, $to = 'array', $format = 'json')
    {
        return $this->get('jms_serializer')->deserialize($data, $to, $format);
    }

    /**
     * @param $data
     * @param int $statusCode
     * @param $group
     * @return Response
     */
    protected function createApiResponse($data, $statusCode = 200, $group = null)
    {
        $json = $this->serialize($data, 'json', $group);

        return new Response($json, $statusCode, array(
            'Content-Type' => 'application/json'
        ));
    }

    /**
     * @param FormInterface $form
     * @throws ApiProblemException
     */
    protected function throwApiProblemValidationException(FormInterface $form)
    {
        $errors = $this->getErrorsFromForm($form);

        $apiProblem = new ApiProblem(
            400,
            ApiProblem::TYPE_VALIDATION_ERROR
        );

        $apiProblem->set('errors', $errors);

        throw new ApiProblemException($apiProblem);
    }

    /**
     * @param FormInterface $form
     * @return array $errors
     */
    protected function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }
}
