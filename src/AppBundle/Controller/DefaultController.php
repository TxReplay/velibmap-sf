<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/api/v1/get/{contract}/{id}", name="retrieve_data_by_contract_and_id")
     */
    public function retrieveDataByContractAndIdAction($contract, $id)
    {
        $jcdecaux = $this->get('app.jcdecaux');

        $data_station = $jcdecaux->getDataByContractAndId($contract, $id);

        $response = new JsonResponse();
        $response->setData([
            'response' => $jcdecaux->pushToFirebase($data_station)
        ]);

        return $response;
    }

    /**
     * @Route("/api/v1/get/{contract}", name="retrieve_data_by_contract")
     */
    public function retrieveDataByContractAction($contract)
    {
        $jcdecaux = $this->get('app.jcdecaux');

        $data_station = $jcdecaux->getDataByContract($contract);

        $response = new JsonResponse();
        $response->setData([
            'response' => $jcdecaux->pushToFirebase($data_station)
        ]);

        return $response;
    }

    /**
     * @Route("/api/v1/expose/{contract}/{id}/{day}/{hour}", name="expose_data")
     */
    public function exposeDataAction($contract, $id, $day, $hour)
    {
        $jcdecaux = $this->get('app.jcdecaux');

        $response = new JsonResponse();
        $response->setData([
            'response' => $jcdecaux->retrieveFromFirebase($contract, $id, $day, $hour)
        ]);

        return $response;
    }
}
