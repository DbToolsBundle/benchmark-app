<?php

namespace App\Command;

use App\Entity\MariaDb as MariaDb;
use App\Entity\MySQL as MySQL;
use App\Entity\PostgreSQL as PostgreSQL;
use App\Entity\SQLite as SQLite;
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
            ->setHelp(<<<TXT
            This command allows you to create dummy data.
            If no option is given, it will create, for all available connections, 100K Customers and, for each one of them:
                - 2 Adresses for all available connections
                - 10 Orders

            So there will be 100K Customers, 200K Adresses and 1 000K Orders.
            TXT)
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'A doctrine connection name. If not given, create dummy data for all available connections'
            )
            ->addOption(
                'customer-entries',
                'ce',
                InputOption::VALUE_OPTIONAL,
                'Number of Customers to create',
                100000
            )
            ->addOption(
                'address-per-customer',
                'apc',
                InputOption::VALUE_OPTIONAL,
                'Number of Adresses to create for each Customer',
                2
            )
            ->addOption(
                'order-per-customer',
                'opc',
                InputOption::VALUE_OPTIONAL,
                'Number of Orders to create for each Customer',
                10
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entitiesDef = [
            'sqlite' => [
                'customer' => SQLite\Customer::class,
                'address' => SQLite\Address::class,
                'order' => SQLite\Order::class,
            ],
            'postgresql' => [
                'customer' => PostgreSQL\Customer::class,
                'address' => PostgreSQL\Address::class,
                'order' => PostgreSQL\Order::class,
            ],
            'mysql' => [
                'customer' => MySQL\Customer::class,
                'address' => MySQL\Address::class,
                'order' => MySQL\Order::class,
            ],
            'mariadb' => [
                'customer' => MariaDb\Customer::class,
                'address' => MariaDb\Address::class,
                'order' => MariaDb\Order::class,
            ],
        ];

        if ($connectionName = $input->getOption('connection')) {
            if (!\array_key_exists($connectionName, $entitiesDef)) {
                throw new \InvalidArgumentException("Connection '". $connectionName ."' not found.");
            }

            $entitiesDef = [
                $connectionName => $entitiesDef[$connectionName],
            ];
        }

        $nbCustomer = (int)$input->getOption('customer-entries');
        $nbAddressPerCustomer = (int)$input->getOption('address-per-customer');
        $nbOrderPerCustomer = (int)$input->getOption('order-per-customer');

        $io->writeln(\sprintf(
            'Creating %s Customer(s) and for each one of them, %s Address(es) and %s Order(s)',
            $nbCustomer,
            $nbAddressPerCustomer,
            $nbOrderPerCustomer
        ));

        foreach($entitiesDef as $managerName => $classeNames) {
            $entityManager = $this->doctrineRegistry->getManager($managerName);

            $io->section($managerName);

            $progressBar = $io->createProgressBar($nbCustomer);
            $progressBar->setFormat('debug');
            $progressBar->start();

            $batchSize = 10;
            // each one of them.
            for ($i = 0; $i < $nbCustomer; $i++) {
                $rand = \uniqid();

                /** @var MySQL\Customer $customer */
                $customer = new $classeNames['customer']();

                $customer
                    ->setEmail($rand . '@example.com')
                    ->setPassword(\md5($rand))
                    ->setLastname('lastname_' . $rand)
                    ->setFirstname('firstname_' . $rand)
                    ->setAge(\rand(15, 60))
                    ->setTelephone('lastname_' . $rand)
                ;

                $entityManager->persist($customer);

                /** @var Array<MySQL\Address> $addresses */
                $addresses = [];
                for ($j = 0; $j < $nbAddressPerCustomer; $j++) {
                    /** @var MySQL\Address $address */
                    $address = new $classeNames['address']();

                    $address
                        ->setCustomer($customer)
                        ->setStreet('street_' . $rand)
                        ->setZipCode('zipcode_' . $rand)
                        ->setCity('city_' . $rand)
                        ->setCountry('country_' . $rand)
                    ;

                    $entityManager->persist($address);
                    $addresses[$j] = $address;
                }

                for ($j = 0; $j < $nbOrderPerCustomer; $j++) {
                    /** @var MySQL\Order $order */
                    $order = new $classeNames['order']();

                    $randKeys = \array_rand($addresses, 2);

                    $order
                        ->setCustomer($customer)
                        ->setEmail($rand . '@example.com')
                        ->setTelephone('telephone_' . $rand)
                        ->setTelephone($rand)
                        ->setCreatedAt(new \DateTimeImmutable(\rand(15, 60) . 'days ago'))
                        ->setAmount(\rand(0, 500))
                        ->setBillingAddress($addresses[$randKeys[0]])
                        ->setShippingAddress($addresses[$randKeys[1]])
                    ;

                    $entityManager->persist($order);
                }

                $progressBar->advance();

                if (($i % $batchSize) === 0) {
                    $entityManager->flush();
                    $entityManager->clear();
                }
            }

            $entityManager->flush();
            $entityManager->clear();

            $progressBar->finish();

            $io->writeln('');
            $io->writeln('');
        }

        return Command::SUCCESS;
    }
}