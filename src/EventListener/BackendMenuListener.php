<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\EventListener;

use MenAtWork\RegistrationInfoMailerBundle\Controller\SendMailToMemberGroupsController;
use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsEventListener(event: 'contao.backend_menu_build', priority: -255)]
class BackendMenuListener
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly AuthorizationCheckerInterface $authorization,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $tree = $event->getTree();

        if ('mainMenu' !== $tree->getName() || !$this->authorization->isGranted('ROLE_ADMIN')) {
            return;
        }

        $contentNode = $tree->getChild('content');

        if (null === $contentNode) {
            return;
        }

        $currentController = (string) $this->requestStack->getCurrentRequest()?->attributes->get('_controller', '');

        $node = $event->getFactory()
            ->createItem('email-news')
            ->setUri($this->router->generate(SendMailToMemberGroupsController::ROUTE_LIST_NAME))
            ->setLabel('E-Mail Nachricht')
            ->setLinkAttribute('title', 'E-Mail an Mitgliedsgruppen senden')
            ->setLinkAttribute('class', 'email-news')
            ->setCurrent(str_contains($currentController, SendMailToMemberGroupsController::class))
        ;

        $contentNode->addChild($node);
    }
}
