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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/mvc-demo")
 */
class MvcDemoController extends Controller
{
    /**
     * @Route("/posts/{id}", name="mvc_demo_post")
     * @Method("GET")
     */
    public function postShowAction($id)
    {
        $post = $this->getDoctrine()->getManager()->find(Post::class, $id);

        if (!$post) {
            throw $this->createNotFoundException("post {$id} not found");
        }

        return $this->render('mvc_demo/post_show.html.twig', ['post' => $post]);
    }
}
