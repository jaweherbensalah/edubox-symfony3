<?php

namespace EduBoxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('EduBoxBundle::stats.html.twig');
    }

    public function listAction()
    {
        return $this->render('EduBoxBundle::stats.html.twig');
    }
}
