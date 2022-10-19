<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
use Exception;

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

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        $result = "";

        $form = $this->createFormBuilder()
            ->add('text', TextType::class, [
                'label' => 'Digite aqui o texto que deseja registrar',
                'attr' => [
                    'placeholder' => 'Digite aqui o texto que deseja registrar',
                ],
                'row_attr' => [
                    'class' => 'form-floating',
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Adicionar'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $response = $this->indexDocument([
                    'index' => 'keywords',
                    'body'  => [ 'keyword' => $data['text']]
                ]);
                $result = $response->asString();
            } catch (ClientResponseException $e) {
                // manage the 4xx error
            } catch (ServerResponseException $e) {
                // manage the 5xx error
            } catch (Exception $e) {
                // eg. network error like NoNodeAvailableException
            }
        }

        return $this->renderForm('search/register.html.twig', [
            'form' => $form,
            'result' => $result
        ]);
    }

    private function runElasticSearch(array $data) {
        $client = ClientBuilder::create()
            ->setHosts(['https://localhost:9200'])
            ->setBasicAuthentication('elastic', 'elastic')
            ->setCABundle($_ENV['ELASTIC_HTTP_CA_PATH'])
            ->build();

        return $client->search($data)->asString();
    }

    private function indexDocument(array $data): Elasticsearch|Promise {
        $client = ClientBuilder::create()
            ->setHosts(['https://localhost:9200'])
            ->setBasicAuthentication('elastic', 'elastic')
            ->setCABundle($_ENV['ELASTIC_HTTP_CA_PATH'])
            ->build();
        
        return $client->index($data);
    }

}
