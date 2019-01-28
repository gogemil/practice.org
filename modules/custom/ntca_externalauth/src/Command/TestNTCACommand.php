<?php

namespace Drupal\ntca_externalauth\Command;

use Drupal\bootstrap\Plugin\Preprocess\Input;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestNTCACommand extends Command {
    protected function configure() {
        $this
            ->setName("ntcaextauth:test")
            ->setDescription('Tests NTCA')
            ->setHelp('You get no help from me.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

    }
}