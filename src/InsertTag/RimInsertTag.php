<?php
declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use MenAtWork\RegistrationInfoMailerBundle\Mailer\MemberTokenContext;

#[AsInsertTag('rim')]
class RimInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(private readonly MemberTokenContext $context)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult($this->context->replace('rim::'.$insertTag->getParameters()->get(0)), OutputType::text);
    }
}
