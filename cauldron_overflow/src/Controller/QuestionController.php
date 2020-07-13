<?php
namespace App\Controller;

use Twig\Environment;
use App\Service\MarkdownHelper;
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
    public function show($slug, MarkdownHelper $markdownHelper)
    {
        dump($this->getParameter('cache_adapter'));
        dump($markdownHelper->isDebug);

        $questionText = 'I\'ve been turned into a cat, any *thoughts* on how to turn back? While I\'m **adorable**, I don\'t really care for cat food.';
        // sans cache
        //$parsedQuestionText = $markdownParser->transformMarkdown($questionText);
        // avec cache
        $parsedQuestionText = $markdownHelper->parse($questionText);

        // pour boucle dans template
        $answers = [
            'Make sure your cat is sitting `purrrfectly` still 🤣',
            'Honestly, I like furry shoes better than MY cat',
            'Maybe... try saying the spell backwards?',
        ];
        
        return $this->render('question/show.html.twig', [
            'question' => ucwords(str_replace('-', ' ', $slug)),
            'questionText' => $parsedQuestionText,
            'answers' => $answers,
        ]);

    }
}