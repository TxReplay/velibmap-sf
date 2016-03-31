<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class FirebaseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:stations:update')
            ->setDescription('Mise à jour des statistiques')
            ->addArgument('contract', InputArgument::REQUIRED, 'Ville où ce trouve la station')
            ->addArgument('station_id', InputArgument::OPTIONAL, 'ID de la station')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jcdecaux = $this->getContainer()->get('app.jcdecaux');
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        if ($input->getArgument('station_id')) {
            $data_station = $jcdecaux->getDataByContractAndId($input->getArgument('contract'), $input->getArgument('station_id'));
        } else {
            $data_station = $jcdecaux->getDataByContract($input->getArgument('contract'));
        }


        $response = $jcdecaux->pushToFirebase($data_station);

        $table = new Table($output);
        $table
            ->setHeaders(['Town', 'Day', 'Hour', 'Available Bike Stands', 'Available Bike'])
            ->setRows([
                [
                    $response['resume']['contract'],
                    $days[$response['resume']['day']],
                    $response['resume']['hour'],
                    $response['resume']['available_bike_stands'],
                    $response['resume']['available_bikes']
                ]
            ])
        ;
        $table->render();
    }
}