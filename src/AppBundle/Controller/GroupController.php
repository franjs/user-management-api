<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Group;
use AppBundle\Form\Type\GroupType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class GroupController extends BaseController
{
    /**
     * @Route("/groups", name="groups_new")
     * @Method("POST")
     *
     *  @ApiDoc(
     *    resource=true,
     *    description="Creates a new Group",
     *  input={
     *     "class" = "AppBundle\Form\Type\GroupType",
     *     "name" = "",
     *  }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function newAction(Request $request)
    {
        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);
        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($group);
        $em->flush();

        $response = $this->createApiResponse($group, Response::HTTP_CREATED);
        $groupUrl = $this->generateUrl('groups_show', ['groupId' => $group->getId()]);
        $response->headers->set('Location', $groupUrl);

        return $response;
    }

    /**
     * @Route("/groups/{groupId}", name="groups_show")
     * @Method("GET")
     *
     *  @ApiDoc(
     *     description="Returns a Group resource",
     *     requirements={
     *         {"name"="groupId", "dataType"="integer", "required"=true, "description"="group id"}
     *     },
     *     statusCodes={
     *         200="Returned when successful",
     *         404="Returned when the group is not found",
     *         401={
     *           "Returned when requires authentication",
     *           "Returned when invalid Token"
     *         }
     *     }
     * )
     *
     * @param $groupId
     * @return Response
     */
    public function showAction($groupId)
    {
        $group = $this->getDoctrine()
            ->getRepository('AppBundle:Group')
            ->find($groupId);

        if (!$group) {
            throw $this->createNotFoundException(sprintf('No group found by ID "%s"', $groupId));
        }

        $response = $this->createApiResponse($group);

        return $response;
    }

    /**
     * @Route("/groups/{groupId}")
     * @Method("DELETE")
     *
     * @ApiDoc(
     *  description="Deletes a Group",
     *  requirements={
     *      {"name"="groupId", "dataType"="integer", "required"=true, "description"="group id"}
     *  }
     * )
     *
     * @param $groupId
     * @return Response
     */
    public function deleteAction($groupId)
    {
        $em = $this->getDoctrine()->getManager();

        $group = $em->getRepository(Group::class)->find($groupId);

        if (!$group) {
            throw $this->createNotFoundException(sprintf('No group found by ID "%s"', $group));
        }

        $members = $em->getRepository('AppBundle:User')->findByGroup($groupId);

        if (count($members)) {
            throw new BadRequestHttpException('this group has members. It can not be deleted !');
        }

        $em->remove($group);
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
