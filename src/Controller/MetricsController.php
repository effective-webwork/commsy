<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController extends AbstractController
{
    /**
     * @Route("/metrics/test")
     */
    public function show()
    {
        return '';
    }
}