<?php declare(strict_types=1);

namespace Laser\Core\Content\MailTemplate;

use Laser\Core\Framework\DataAbstractionLayer\EntityCollection;
use Laser\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<MailTemplateEntity>
 */
#[Package('sales-channel')]
class MailTemplateCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'mail_template_collection';
    }

    protected function getExpectedClass(): string
    {
        return MailTemplateEntity::class;
    }
}