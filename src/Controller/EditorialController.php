<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class EditorialController extends AbstractController
{
    /**
     * @Route("/mentions", name="editorial_mentions")
     */
    public function index()
    {
        return $this->render('editorial/mentions.html.twig');
    }
}
