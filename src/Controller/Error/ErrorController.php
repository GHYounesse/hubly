<?php

// src/Controller/Error/ErrorController.php
namespace App\Controller\Error;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
/**
 * Class ErrorController
 * @package App\Controller\Error
 * 
 * Controller responsible for rendering custom error pages.
 *
 * - Determines the HTTP status code from the exception.
 * - Chooses a specific Twig template for the status code if it exists,
 *   otherwise falls back to a generic error template.
 * - Renders the template with the status code and exception details.
 */
class ErrorController extends AbstractController
{   
    /**
     * Displays an error page for a given exception.
     *
     * @param Environment $twig Twig environment used to render templates.
     * @param FlattenException $exception The flattened exception containing error details.
     *
     * @return Response The rendered error page with the appropriate HTTP status code.
     */
    public function show(Environment $twig, FlattenException $exception): Response
    {
        // Get the HTTP status code
        $statusCode = $exception->getStatusCode();

        // Choose template based on status code
        $template = "bundles/TwigBundle/Exception/error{$statusCode}.html.twig";

        // Fallback template if specific one doesn't exist
        if (!file_exists($this->getParameter('kernel.project_dir') . "/templates/$template")) {
            $template = 'bundles/TwigBundle/Exception/error.html.twig';
        }

        return new Response(
            $twig->render($template, [
                'status_code' => $statusCode,
                'exception' => $exception,
            ]),
            $statusCode
        );
    }
}
