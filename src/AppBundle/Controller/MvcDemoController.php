<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Form\PostType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/mvc-demo")
 */
class MvcDemoController extends Controller
{
    /**
     * @Route("/posts/{id}", name="mvc_demo_post", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function postShowAction($id)
    {
        //MODELO - buscamos con doctrine el post por su id
        $post = $this->getDoctrine()->getManager()->find(Post::class, $id);

        //si el post no existe levantamos un 404 en forma de excepción.
        //esto nos permite una mejor gestión de los errores que si aquí devolviésemos una Response con status-code=404
        if (!$post) {
            throw new NotFoundHttpException("post {$id} not found");
        }

        //VISTA - le pedimos a twig que renderize la plantilla post_show usando como parámetros el objeto post
        $view = $this->renderView('mvc_demo/post_show.html.twig', ['post' => $post]);

        //RESPONSE - el controlador siempre debe devolver una response
        return new Response($view, 200);
    }

    /**
     * Displays a form to edit an existing Post entity.
     *
     * @Route("/posts/new", name="mvc_demo_post_new")
     * @Method({"GET", "POST"})
     */
    public function PostEditAction(Request $request)
    {
        //bloqueamos el acceso a usuarios anónimos
        if (!$this->container->get("security.authorization_checker")->isGranted("ROLE_ADMIN")) {
            throw $this->createAccessDeniedException("You must be admin to be here");
        }

        //creamos el nuevo post
        $post = new Post();

        //creamos el formulario
        $form = $this->container->get('form.factory')->create(PostType::class, $post);

        //si la petición es un post le decimos al formulario que se hidrate con la request
        if ("POST" === $request->getMethod()) {
            $form->handleRequest($request);

            //comprobamos si el formulario es válido,
            //de los errores no nos preocupamos porque ya los gestiona el propio form,
            //por eso no hay else
            if ($form->isSubmitted() && $form->isValid()) {
                //persistimos el nuevo post
                $entityManager = $this->get("doctrine.orm.entity_manager");
                $entityManager->persist($post);
                $entityManager->flush();

                //añadimos a la sesión el típico mensaje flash que sólo se verá una vez.
                $this->addFlash('success', 'post created successfully');

                //generamos la ruta en la que se ve el post recién creado
                $route = $this->container->get('router')->generate('mvc_demo_post', ['id' => $post->getId()]);

                //enviamos un email al admin del blog
                $this->sendPostCreatedEmail($route);

                //devolvemos la redirección a la ruta del post view
                return new RedirectResponse($route);
            }
        }

        //devolvemos la respuesta con la vista del formulario,
        // por aquí pasará cuando la petición sea get o cuando el submit tenga errores
        return $this->render('admin/blog/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    private function sendPostCreatedEmail($route)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('new post')
            ->setFrom('send@example.com')
            ->setTo('recipient@example.com')
            ->setBody('someone created a new post. Its route: ' . $route)
        ;

        $this->container->get('mailer')->send($message);
    }
}
