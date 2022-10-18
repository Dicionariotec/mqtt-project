<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Elastic\Elasticsearch\ClientBuilder;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(Request $request): Response
    {
        $form = $this->createFormBuilder()
            ->add('keyword', TextType::class, [
                'label' => 'Digite aqui para pesquisar',
                'attr' => [
                    'placeholder' => 'Digite aqui para pesquisar',
                ],
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Pesquisar'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            return $this->renderForm('search/search.html.twig', [
                'form' => $form,
                'results' => $this->runElasticSearch(data: $data)
            ]);
        }

        return $this->renderForm('search/search.html.twig', [
            'form' => $form,
            'results' => ''
        ]);
    }

    private function runElasticSearch(array $data) {
        $client = ClientBuilder::create()
            ->setHosts(['https://localhost:9200'])
            ->setBasicAuthentication('elastic', 'elastic')
            ->setCABundle($_ENV['ELASTIC_HTTP_CA_PATH'])
            ->build();

        // Info API
        return $client->info();
    }

}
