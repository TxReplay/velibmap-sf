<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FirebaseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:stations:update')
            ->setDescription('Mise à jour des statistiques')
            ->addArgument('contract', InputArgument::REQUIRED, 'Ville où ce trouve la station')
            ->addArgument('station_id', InputArgument::REQUIRED, 'ID de la station')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jcdecaux = $this->getContainer()->get('app.jcdecaux');

        $data_station = $jcdecaux->getData($input->getArgument('contract'), $input->getArgument('station_id'));
        $response = $jcdecaux->pushToFirebase($data_station);

        $output->writeln('Success !');
        $output->writeln('Available Bike Stands : ' . $response['available_bike_stands']);
        $output->writeln('Available Bike : ' . $response['available_bikes']);
    }
}