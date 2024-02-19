<?php

namespace App\Command;

use App\Entity\MariaDB\Customer as MariaDBCustomer;
use App\Entity\MySQL\Customer as MySQLCustomer;
use App\Entity\PostgreSQL\Customer as PostgreSQLCustomer;
use App\Entity\SQLite\Customer as SQLiteCustomer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:dummy-data', description: 'Create dummy data')]
class DummyDataCommand extends Command
{
    public function __construct(
        private ManagerRegistry $doctrineRegistry,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setHelp('Create some dummy data')
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'A doctrine connection name. If not given, create dummy data for all available connections'
            )
            ->addOption(
                'sample-size',
                's',
                InputOption::VALUE_OPTIONAL,
                'Number of entries to create (default is 100 000)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entitiesDef = [
            'sqlite' => SQLiteCustomer::class,
            'postgresql' => PostgreSQLCustomer::class,
            'mysql' => MySQLCustomer::class,
            'mariadb' => MariaDBCustomer::class,
        ];

        if ($connectionName = $input->getOption('connection')) {
            if (!\array_key_exists($connectionName, $entitiesDef)) {
                throw new \InvalidArgumentException("Connection '". $connectionName ."' not found.");
            }

            $entitiesDef = [
                $connectionName => $entitiesDef[$connectionName],
            ];
        }

        $sampleSize = (int)$input->getOption('sample-size') ?? 100000;


        $io->writeln('Creating ' . $sampleSize . ' entries.');

        foreach($entitiesDef as $managerName => $customerClass) {
            $entityManager = $this->doctrineRegistry->getManager($managerName);

            $io->section($managerName);

            // Let's create n Customers per batch of 200
            $progressBar = $io->createProgressBar($sampleSize);
            $progressBar->setFormat('debug');
            $progressBar->start();

            $batchSize = 100;
            // each one of them.
            for ($i = 0; $i < $sampleSize; $i++) {
                $rand = \uniqid();

                /** @var MySQLCustomer $customer */
                $customer = new $customerClass();

                $customer
                    ->setEmail($rand . '@example.com')
                    ->setPassword(\md5($rand))
                    ->setLastname('lastname_' . $rand)
                    ->setFirstname('firstname_' . $rand)
                    ->setLevel('level_' . $rand)
                    ->setAge(\rand(15, 60))
                    ->setStreet('street_' . $rand)
                    ->setZipCode('zip_code_' . $rand)
                    ->setCity('city_' . $rand)
                    ->setCountry('country_' . $rand)
                ;

                $entityManager->persist($customer);

                $progressBar->advance();

                if (($i % $batchSize) === 0) {
                    $entityManager->flush();
                    $entityManager->clear();
                }
            }

            $entityManager->flush();
            $entityManager->clear();

            $io->writeln('');
            $io->writeln('');
        }

        return Command::SUCCESS;
    }
}