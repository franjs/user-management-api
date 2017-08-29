<?php

namespace AppBundle\Controller;

use AppBundle\Form\Model\LoginModel;
use AppBundle\Form\Type\LoginType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;


class SecurityController extends BaseController
{
    /**
     * @Route("/login", name="login")
     * @Method("POST")
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns a Token",
     *  input={
     *     "class" = "AppBundle\Form\Type\LoginType",
     *     "name" = "",
     *  }
     * )
     *
     * @param $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $login = new LoginModel();
        $form = $this->createForm(LoginType::class, $login);

        $this->processForm($request, $form);

        if (!$form->isValid()){
            $this->throwApiProblemValidationException($form);
        }

        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findOneBy(['username' => $login->getUsername()]);

        if (!$user) {
            throw $this->createNotFoundException();
        }

        $isValid = $this->get('security.password_encoder')
            ->isPasswordValid($user, $login->getPassword());

        if (!$isValid) {
            throw new BadCredentialsException();
        }

        $token = $this->get('lexik_jwt_authentication.encoder')->encode([
            'username' => $user->getUsername(),
            'exp' => time() + 3600 // 1 hour expiration
        ]);

        $response = $this->createApiResponse(['token' => $token, 200]);

        return $response;
    }
}
