<?php
namespace App\Controller;

use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class QuestionController extends AbstractController
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage(Environment $twigEnvironment)
    {
        // reponse texte basique
        // return new Response('What a bewitching controller we have conjured!');

        // reponse avec template twig
        return $this->render('question/homepage.html.twig');

        // // En utilisant directement la classe Twig
        // $html = $twigEnvironment->render('question/homepage.html.twig');
        // return new Response($html);
    }

    /**
     * @Route("/questions/{slug}", name="app_question_show")
     */
    public function show($slug)
    {
        // 1/ reponse texte basique
        //return new Response('Future page to show a question!');

        // 2/ reponse basique utilisant parametre de l'action
        // return new Response(sprintf(
        //     'Future page to show the question "%s"!',
        //     ucwords(str_replace('-', ' ', $slug))
        // ));

        // 3/ reponse avec template twig
        // return $this->render('question/show.html.twig', [
        //     'question' => ucwords(str_replace('-', ' ', $slug))
        // ]);

        // 4/ boucle dans template
        $answers = [
            'Make sure your cat is sitting purrrfectly still 🤣',
            'Honestly, I like furry shoes better than MY cat',
            'Maybe... try saying the spell backwards?',
        ];
        
        return $this->render('question/show.html.twig', [
            'question' => ucwords(str_replace('-', ' ', $slug)),
            'answers' => $answers,
        ]);

    }
}