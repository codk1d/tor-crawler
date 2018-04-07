<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use AppBundle\Entity\OnionWord;

class UpdateOnionWordsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:update:onion-words')
            ->setDescription('Update OnionWord entries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$em = $this->getContainer()->get('doctrine')->getManager();
        $iOnion = 0;

        $onions = $em->getRepository("AppBundle:Onion")->findAll();
        foreach($onions as $o) {
            $onion = $em->getRepository("AppBundle:Onion")->find($o->getId());
            $onionWords = $em->getRepository("AppBundle:OnionWord")->findForOnionPerWordId($onion);

            $iOnionWord = 0;

            $wordsForOnion = $em->getRepository("AppBundle:Word")->findForOnion($onion);
            foreach($wordsForOnion as $word) {
                if(isset($onionWords[$word->getId()])) {
                    $onionWord = $onionWords[$word->getId()];
                } else {
                    $onionWord = new OnionWord();
                    $onionWord->setOnion($onion);
                    $onionWord->setWord($word);
                }

                $onionWord->setDateUpdated(new \DateTime());

                $count = $em->getRepository("AppBundle:ResourceWord")->sumCountsForOnionAndWord($onion, $word);
                $onionWord->setCount($count);

                $countResources = $em->getRepository("AppBundle:ResourceWord")->countForOnionAndWord($onion, $word);
                $onionWord->setCountResources($countResources);

                $average = 0;
                if($countResources > 0) {
                    $average = round($count / $countResources, 3);
                }
                $onionWord->setAverage($average);

                $em->persist($onionWord);

                $iOnionWord++;
                if($iOnionWord%100 == 0) {
                    $em->flush();
                }
            }

            $em->flush();

            $i++;
            if($i%20 == 0 || count($wordsForOnion) > 100) {
                $em->clear();
            }

            $output->write(".");
        }

        $output->writeln(" End.");
    }
}