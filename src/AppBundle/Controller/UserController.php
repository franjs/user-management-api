<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Group;
use AppBundle\Entity\User;
use AppBundle\Form\Type\GroupIdType;
use AppBundle\Form\Type\UserType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class UserController
 * @package AppBundle\Controller
 *
 * @Security("is_granted('ROLE_ADMIN')")
 */
class UserController extends BaseController
{
    /**
     * @Route("/users", name="users_new")
     * @Method("POST")
     *
     *  @ApiDoc(
     *     resource=true,
     *     description="Creates a new User",
     *  input={
     *     "class" = "AppBundle\Form\Type\UserType",
     *     "name" = "",
     *  }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function addUserAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $response = $this->createApiResponse($user, Response::HTTP_CREATED);
        $userUrl = $this->generateUrl('users_show', ['userId' => $user->getId()]);
        $response->headers->set('Location', $userUrl);

        return $response;
    }

    /**
     * @Route("/users/{userId}", name="users_show")
     * @Method("GET")
     *
     *  @ApiDoc(
     *     description="Returns a User resource",
     *     requirements={
     *         {"name"="userId", "dataType"="integer", "required"=true, "description"="user id"}
     *     },
     *     statusCodes={
     *         200="Returned when successful",
     *         404="Returned when the user is not found",
     *         401={
     *           "Returned when requires authentication",
     *           "Returned when invalid Token"
     *         }
     *     }
     * )
     *
     * @param $userId
     * @return Response
     */
    public function showAction($userId)
    {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException(sprintf('No user found by ID "%s"', $userId));
        }

        $response = $this->createApiResponse($user);

        return $response;
    }

    /**
     * @Route("/users/{userId}")
     * @Method("DELETE")
     *
     * @ApiDoc(
     *  description="Deletes a User",
     *  requirements={
     *      {"name"="userId", "dataType"="integer", "required"=true, "description"="user id"}
     *  }
     * )
     *
     * @param $userId
     * @return Response
     */
    public function deleteAction($userId)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw $this->createNotFoundException(sprintf('No user found by ID "%s"', $userId));
        }

        $em->remove($user);
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/users/{userId}/assign-to-group", name="assign_user_to_group")
     * @Method("POST")
     *
     *  @ApiDoc(
     *    description="Assign a User to a Group",
     *    parameters={
     *      {"name"="group_id", "dataType"="integer", "required"=true, "description"="group id"}
     *  }
     * )
     *
     * @param Request $request
     * @param int $userId
     * @return Response
     */
    public function assignToGroupAction(Request $request, $userId)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw $this->createNotFoundException(sprintf('No user found by ID "%s"', $userId));
        }

        $form = $this->createForm(GroupIdType::class);
        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }

        $groupId = $form->get('group_id')->getData();
        $group = $em->getRepository(Group::class)->find($groupId);

        if (!$group) {
            throw $this->createNotFoundException(sprintf('No group found by ID "%s"', $groupId));
        }

        if ($user->isMemberOf($group)) {
            throw new BadRequestHttpException('The user is already assigned to the group given');
        }

        $user->assignTo($group);

        $em->persist($user);
        $em->flush();

        $response = $this->createApiResponse($user);

        return $response;
    }

    /**
     * @Route("/users/{userId}/remove-from-group", name="user_user_from_group")
     * @Method("POST")
     *
     *  @ApiDoc(
     *    description="Remove a User from a Group",
     *    parameters={
     *      {"name"="group_id", "dataType"="integer", "required"=true, "description"="group id"}
     *  }
     * )
     *
     * @param Request $request
     * @param int $userId
     * @return Response
     */
    public function removeFromGroupAction(Request $request, $userId)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw $this->createNotFoundException(sprintf('No user found by ID "%s"', $userId));
        }

        $form = $this->createForm(GroupIdType::class);
        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }

        $groupId = $form->get('group_id')->getData();
        $group = $em->getRepository(Group::class)->find($groupId);

        if (!$group) {
            throw $this->createNotFoundException(sprintf('No group found by ID "%s"', $groupId));
        }

        if (!$user->isMemberOf($group)) {
            throw new BadRequestHttpException('The user is not a member of the group given');
        }

        $user->removeFrom($group);

        $em->persist($user);
        $em->flush();

        $response = $this->createApiResponse($user);

        return $response;
    }
}
