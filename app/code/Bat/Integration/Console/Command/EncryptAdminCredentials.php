<?php
namespace Bat\Integration\Console\Command;

use Bat\Integration\Helper\Data;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @class EncryptAdminCredentials
 * Encrypt admin username and password
 */
class EncryptAdminCredentials extends Command
{
    const USERNAME = 'username';
    const PASSWORD = 'password';

    /**
     * @var Data
     */
    private Data $data;

    /**
     * @param Data $data
     * @param string|null $name
     */
    public function __construct(
        Data $data,
        string $name = null
    ) {
        $this->data = $data;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('encrypt:admin:credentials');
        $this->setDescription('Encrypt Admin credentials');
        $this->addOption(
            self::USERNAME,
            null,
            InputOption::VALUE_REQUIRED,
            'Admin Username'
        );
        $this->addOption(
            self::PASSWORD,
            null,
            InputOption::VALUE_REQUIRED,
            'Admin Password'
        );

        parent::configure();
    }

    /**
     * Get Encrypted Request
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $validate = $this->validate($options);
        if ($validate) {
            if ($this->data->canDoEncryptionDecryption()) {
                $result['username'] = $this->data->encryptData($options[self::USERNAME]);
                $result['password'] = $this->data->encryptData($options[self::PASSWORD]);
                $output->writeln('<info>Success</info>');
                $output->writeln('<info>Encrypted json request :</info>');
                $output->writeln('<info>'.json_encode($result).'</info>');
            } else {
                $output->writeln('<info>Failure</info>');
                $output->writeln('<error>Update the configuration for Data Encryption and Decryption</error>');
            }
            return 1;
        } else {
            $output->writeln('<info>Failure</info>');
            $output->writeln('<error>Pass the required parameters username and password</error>');
        }
        return 0;
    }

    /**
     * Validate Input
     *
     * @param array $options
     * @return bool
     */
    public function validate($options)
    {
        if (isset($options[self::USERNAME]) && isset($options[self::PASSWORD])) {
            $userName = $options[self::USERNAME];
            $password = $options[self::PASSWORD];
            if (trim($userName) != '' && trim($password) != '') {
                return true;
            }
        }
        return false;
    }
}
