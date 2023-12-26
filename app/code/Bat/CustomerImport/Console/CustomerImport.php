<?php

namespace Bat\CustomerImport\Console;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Bat\CustomerImport\Model\ImportCustomer as ImportCustomerModel;

class CustomerImport extends Command
{
    /**
     * NAME
     */
    public const FILE_PAT_NAME = 'file_name_path';

    /**
     * @var ImportCustomerModel
     */
    protected ImportCustomerModel $importCustomer;

    /**
     * @param ImportCustomer $importCustomer
     */
    public function __construct(ImportCustomerModel $importCustomer)
    {
        $this->importCustomer = $importCustomer;
        parent::__construct('mycommand');
    }

    /**
     * Command configure
     */
    protected function configure()
    {

        $options = [
            new InputOption(
                self::FILE_PAT_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Name'
            )
        ];

        $this->setName('batimport:customer')
            ->setDescription('Customer import command line')
            ->setDefinition($options);
        parent::configure();
    }

     /**
      * Execute the command
      *
      * @param InputInterface $input
      * @param OutputInterface $output
      *
      * @return int
      */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exitCode = 0;
         
        if ($name = $input->getOption(self::FILE_PAT_NAME)) {
            $output->writeln('<info>Customer import: `' . $name . '`</info>');
            $responses = $this->importCustomer->customers($name);
            foreach ($responses as $response) {
                 $output->writeln($response);
            }
        }

         $output->writeln('<info>Success customer import.</info>');
         $output->writeln('<comment>Customer import.</comment>');

        try {
            if (rand(0, 1)) {
                throw new LocalizedException(__('An error occurred.'));
            }
        } catch (LocalizedException $e) {
            $output->writeln(sprintf(
                '<error>%s</error>',
                $e->getMessage()
            ));
            $exitCode = 1;
        }
         
         return $exitCode;
    }
}
