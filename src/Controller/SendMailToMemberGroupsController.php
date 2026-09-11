<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\Controller;

use Contao\BackendCustom;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use MenAtWork\RegistrationInfoMailerBundle\Utility\GroupOfMembers;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Terminal42\NotificationCenterBundle\NotificationCenter;

#[IsGranted('ROLE_ADMIN')]
class SendMailToMemberGroupsController extends AbstractController
{
    public const ROUTE_LIST_NAME = 'backend_send_mail_to_customer_list';
    public const ROUTE_SEND_NAME = 'backend_send_mail_to_customer_send';
    private const MASS_MAIL_NOTIFICATION_TITLE = 'E-Mail an Mitglieder';

    /**
     * @var array<int, int>
     */
    private array $countId = [];

    /**
     * @var array<int, int>
     */
    private array $groupRecipientCount = [];

    public function __construct(
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly GroupOfMembers $groupOfMembers,
        private readonly NotificationCenter $notificationCenter,
    ) {
    }

    #[Route(
        path: '/contao/my-backend-route',
        name: self::ROUTE_LIST_NAME,
        methods: ['GET'],
        defaults: ['_scope' => 'backend'],
    )]
    public function getAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $results = $request->getSession()->getFlashBag()->get('rim_bulk_mail_result');
        $result = $results ? $results[array_key_last($results)] : [];

        return $this->renderBackendPage(array_replace([
            'groupNames' => $this->groupOfMembers->getAllExistingGroups(),
            'token' => $this->csrfTokenManager->getDefaultTokenValue(),
            'sendToken' => $this->csrfTokenManager->getToken('rim_bulk_mail')->getValue(),
            'selectedGroupIds' => [],
            'membersCount' => 0,
            'emailCount' => [],
        ], $result));
    }

    #[Route(
        path: '/contao/my-backend-route',
        name: self::ROUTE_SEND_NAME,
        methods: ['POST'],
        defaults: ['_scope' => 'backend'],
    )]
    public function postAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('rim_bulk_mail', (string) $request->request->get('rim_token', '')))) {
            throw $this->createAccessDeniedException('Invalid mail form token.');
        }

        $selectedGroupIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', (array) $request->request->all('groups')),
                ),
            ),
        );

        $members = $this->groupOfMembers->getMembersOfGroupIds($selectedGroupIds);
        [$recipientsByGroup, $uniqueRecipients] = $this->collectRecipients($members);
        $notificationId = $this->resolveMassMailNotificationId();
        $sentRecipients = [];

        if (null !== $notificationId) {
            foreach ($uniqueRecipients as $recipientKey => $member) {
                if ($this->sendMailToMember($member, $notificationId)) {
                    $sentRecipients[$recipientKey] = true;
                }
            }
        }

        $this->buildGroupStatistics($selectedGroupIds, $recipientsByGroup, $sentRecipients);
        $sentCount = \count($sentRecipients);

        $request->getSession()->getFlashBag()->add('rim_bulk_mail_result', [
            'selectedGroupIds' => $selectedGroupIds,
            'membersCount' => $sentCount,
            'emailCount' => $this->countId,
            'groupRecipientCount' => $this->groupRecipientCount,
            'sentCount' => $sentCount,
            'notificationConfigured' => null !== $notificationId,
        ]);

        return $this->redirectToRoute(self::ROUTE_LIST_NAME, [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderBackendPage(array $context): Response
    {
        $backendCustom = new BackendCustom();
        $template = $backendCustom->getTemplateObject();
        $template->headline = 'E-Mail Nachricht';
        $template->title = 'E-Mail Nachricht';
        $template->main = $this->renderView('@RegistrationInfoMailer/mail_to_member.html.twig', $context);

        return $backendCustom->run();
    }

    /**
     * @param array<string, mixed> $member
     */
    private function sendMailToMember(array $member, int $notificationId): bool
    {
        $emailAddress = (string) ($member['email'] ?? '');

        if ('' === $emailAddress) {
            return false;
        }

        try {
            $receipts = $this->notificationCenter->sendNotification(
                $notificationId,
                ['form_email' => $emailAddress],
                null,
            );

            return \count($receipts) > 0 && $receipts->wereAllDelivered();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param list<array<string, mixed>> $members
     *
     * @return array{
     *     0: array<int, array<string, bool>>,
     *     1: array<string, array<string, mixed>>
     * }
     */
    private function collectRecipients(array $members): array
    {
        $recipientsByGroup = [];
        $uniqueRecipients = [];

        foreach ($members as $member) {
            $email = trim((string) ($member['email'] ?? ''));

            if ('' === $email) {
                continue;
            }

            $groupId = (int) ($member['group_id'] ?? 0);
            $recipientKey = $this->buildRecipientKey($member, $email);

            if ($groupId > 0) {
                $recipientsByGroup[$groupId][$recipientKey] = true;
            }

            if (!isset($uniqueRecipients[$recipientKey])) {
                $uniqueRecipients[$recipientKey] = $member;
            }
        }

        return [$recipientsByGroup, $uniqueRecipients];
    }

    /**
     * @param list<int>                        $selectedGroupIds
     * @param array<int, array<string, bool>>  $recipientsByGroup
     * @param array<string, bool>              $sentRecipients
     */
    private function buildGroupStatistics(array $selectedGroupIds, array $recipientsByGroup, array $sentRecipients): void
    {
        $this->countId = [];
        $this->groupRecipientCount = [];

        foreach ($selectedGroupIds as $groupId) {
            $recipientKeys = array_keys($recipientsByGroup[$groupId] ?? []);
            $this->groupRecipientCount[$groupId] = \count($recipientKeys);

            $sentCount = 0;

            foreach ($recipientKeys as $recipientKey) {
                if (isset($sentRecipients[$recipientKey])) {
                    ++$sentCount;
                }
            }

            $this->countId[$groupId] = $sentCount;
        }
    }

    private function buildRecipientKey(array $member, string $email): string
    {
        $memberId = (int) ($member['id'] ?? 0);

        if ($memberId > 0) {
            return 'member:'.$memberId;
        }

        return 'email:'.strtolower($email);
    }

    private function resolveMassMailNotificationId(): ?int
    {
        $notifications = $this->notificationCenter->getNotificationsForNotificationType('core_form');
        $notificationId = array_search(self::MASS_MAIL_NOTIFICATION_TITLE, $notifications, true);

        if (false !== $notificationId) {
            return (int) $notificationId;
        }

        return null;
    }
}
