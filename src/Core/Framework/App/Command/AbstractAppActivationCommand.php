<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Command;

use Laser\Core\Framework\Adapter\Console\LaserStyle;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Laser\Core\Framework\Log\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal only for use by the app-system, will be considered internal from v6.4.0 onward
 */
#[Package('core')]
abstract class AbstractAppActivationCommand extends Command
{
    protected EntityRepository $appRepo;

    public function __construct(
        EntityRepository $appRepo,
        private readonly string $action
    ) {
        $this->appRepo = $appRepo;

        parent::__construct();
    }

    abstract public function runAction(string $appId, Context $context): void;

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new LaserStyle($input, $output);
        $context = Context::createDefaultContext();

        $appName = $input->getArgument('name');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $appName));

        $id = $this->appRepo->searchIds($criteria, $context)->firstId();

        if (!$id) {
            $io->error("No app found for \"{$appName}\".");

            return self::FAILURE;
        }

        $this->runAction($id, $context);

        $io->success(sprintf('App %sd successfully.', $this->action));

        return self::SUCCESS;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the app, has also to be the name of the folder under
                which the app can be found under custom/apps');
    }
}
