<?php
declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle\Tests;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Module;
use Doctrine\DBAL\Connection;
use MenAtWork\RegistrationInfoMailerBundle\EventListener\DataContainer\MemberSubmitCallbackListener;
use MenAtWork\RegistrationInfoMailerBundle\EventListener\RegistrationInfoMailerHookListener;
use MenAtWork\RegistrationInfoMailerBundle\Mailer\MemberNotificationSender;
use MenAtWork\RegistrationInfoMailerBundle\Mailer\MemberTokenContext;
use MenAtWork\RegistrationInfoMailerBundle\Utility\GroupOfMembers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use Terminal42\NotificationCenterBundle\Receipt\ReceiptCollection;

class MailerTest extends TestCase
{
    public function testManagerPluginAndBundleResourcePaths(): void
    {
        $plugin = new \MenAtWork\RegistrationInfoMailerBundle\ContaoManager\Plugin();
        $configs = $plugin->getBundles($this->createMock(\Contao\ManagerPlugin\Bundle\Parser\ParserInterface::class));
        self::assertCount(1, $configs);
        self::assertContains(\Terminal42\NotificationCenterBundle\Terminal42NotificationCenterBundle::class, $configs[0]->getLoadAfter());
        $bundle = $configs[0]->getBundleInstance($this->createMock(\Symfony\Component\HttpKernel\KernelInterface::class));
        self::assertFileExists($bundle->getPath().'/contao/dca/tl_module.php');
        self::assertFileExists($bundle->getPath().'/templates/mail_to_member.html.twig');
    }

    public function testRegistrationWithoutSelectedNotificationLogsAnError(): void
    {
        $sender = $this->createMock(MemberNotificationSender::class);
        $sender->expects(self::never())->method('send');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('log')->with('error', self::anything(), self::anything());
        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('getAdapter')->willReturn(new Adapter(\Contao\Controller::class));
        $listener = new RegistrationInfoMailerHookListener($this->createMock(Connection::class), $sender, $logger, $framework);
        $module = $this->getMockBuilder(Module::class)->disableOriginalConstructor()->onlyMethods(['compile'])->getMock();
        $module->rim_active = '1';
        $module->rim_mailtemplate = 0;
        $listener->onCreateNewUser(42, [], $module);
    }

    public function testFailedDeliveryDoesNotLogSuccess(): void
    {
        $sender = $this->createMock(MemberNotificationSender::class);
        $sender->expects(self::once())->method('send')->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('log');
        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('getAdapter')->willReturn(new Adapter(\Contao\Controller::class));
        $listener = new RegistrationInfoMailerHookListener($this->createMock(Connection::class), $sender, $logger, $framework);
        $module = $this->getMockBuilder(Module::class)->disableOriginalConstructor()->onlyMethods(['compile'])->getMock();
        $module->rim_active = '1'; $module->rim_mailtemplate = 11; $module->rim_do_syslog = '1';
        $listener->onCreateNewUser(42, [], $module);
    }

    public function testLegacyAndPrefixedTokensKeepEmptyValuesAndExcludeSecrets(): void
    {
        $context = new MemberTokenContext();
        $tokens = $context->prepare(['firstname'=>'Ada', 'company'=>'', 'login'=>0, 'password'=>'secret', 'session'=>'secret']);
        self::assertSame('Ada', $tokens['firstname']);
        self::assertSame('Ada', $tokens['member_firstname']);
        self::assertSame('Ada', $tokens['member_raw_firstname']);
        self::assertSame('', $tokens['member_company']);
        self::assertSame(0, $tokens['member_login']);
        self::assertArrayNotHasKey('password', $tokens);
        self::assertArrayNotHasKey('member_password', $tokens);
        self::assertArrayNotHasKey('session', $tokens);
    }

    public function testInsertTagsDoNotLeakBetweenNestedMessagesOrExceptions(): void
    {
        $context = new MemberTokenContext();
        self::assertFalse($context->replace('other::firstname'));
        $context->withTokens(['firstname'=>'Ada'], function () use ($context): void {
            self::assertSame('Ada', $context->replace('rim::firstname'));
            try {
                $context->withTokens(['firstname'=>'Grace'], function () use ($context): void {
                    self::assertSame('Grace', $context->replace('rim::firstname'));
                    throw new \RuntimeException('Delivery failed');
                });
            } catch (\RuntimeException) {
                self::assertSame('Ada', $context->replace('rim::firstname'));
            }
        });
        self::assertSame('', $context->replace('rim::firstname'));
    }

    public function testMissingNotificationMessagesAreReportedAsFailure(): void
    {
        $nc = $this->createMock(NotificationCenter::class);
        $nc->method('sendNotification')->willReturn(new ReceiptCollection());
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        self::assertFalse((new MemberNotificationSender($nc, new MemberTokenContext(), $logger))->send(1, []));
    }

    public function testDeliveryExceptionClearsLegacyTokenContext(): void
    {
        $nc = $this->createMock(NotificationCenter::class);
        $nc->method('sendNotification')->willThrowException(new \RuntimeException('Transport unavailable'));
        $context = new MemberTokenContext();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        self::assertFalse((new MemberNotificationSender($nc, $context, $logger))->send(1, ['firstname'=>'Ada']));
        self::assertSame('', $context->replace('rim::firstname'));
    }

    #[DataProvider('statusChanges')]
    public function testStatusTransitionsSendOnceOnly(array $beforeChanges, array $afterChanges, ?int $expectedNotification): void
    {
        $base = ['id'=>42, 'login'=>1, 'disable'=>0, 'start'=>'', 'stop'=>'', 'rim_send_mail'=>'1', 'rim_activate_mailtemplate'=>11, 'rim_deactivate_mailtemplate'=>12, 'language'=>'de'];
        $before = array_replace($base, $beforeChanges);
        $after = array_replace($base, $afterChanges);
        $db = $this->createMock(Connection::class);
        $db->method('fetchAssociative')->willReturnOnConsecutiveCalls($before, $after, $after);
        $sender = $this->createMock(MemberNotificationSender::class);
        if ($expectedNotification === null) {
            $sender->expects(self::never())->method('send');
        } else {
            $sender->expects(self::once())->method('send')->with($expectedNotification, $after, 'de')->willReturn(true);
        }
        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('getAdapter')->willReturn(new Adapter(DefaultTemplates::class));
        $listener = new MemberSubmitCallbackListener($db, $sender, new RequestStack(), $framework);
        $member = (object) ['id'=>42];
        $listener->onLoad($member);
        $listener->onSubmit($member);
        $listener->onSubmit($member);
    }

    public static function statusChanges(): iterable
    {
        yield 'enable login' => [['login'=>0], [], 11];
        yield 'disable login' => [[], ['login'=>0], 12];
        yield 'enable disabled account' => [['disable'=>1], [], 11];
        yield 'disable account' => [[], ['disable'=>1], 12];
        yield 'unchanged account' => [[], [], null];
        yield 'only name edited' => [[], ['firstname'=>'Ada'], null];
        yield 'mail checkbox off' => [['login'=>0], ['rim_send_mail'=>''], null];
        yield 'future start keeps account inactive' => [['login'=>0], ['start'=>PHP_INT_MAX], null];
        yield 'expired stop keeps account inactive' => [['login'=>0], ['stop'=>1], null];
        yield 'expired account activated' => [['stop'=>1], [], 11];
        yield 'global activation default' => [['login'=>0], ['rim_activate_mailtemplate'=>0], 11];
        yield 'global deactivation default' => [[], ['login'=>0,'rim_deactivate_mailtemplate'=>0], 12];
    }

    #[DataProvider('loggingFlags')]
    public function testRegistrationLoggingUsesPsrLogger(bool $enabled): void
    {
        $db = $this->createMock(Connection::class);
        $sender = $this->createMock(MemberNotificationSender::class);
        $sender->expects(self::once())->method('send')->with(11, self::callback(static fn ($data) => $data['id'] === 42), 'de')->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($enabled ? self::once() : self::never())->method('log')->with('info', self::anything(), self::anything());
        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('getAdapter')->willReturn(new Adapter(\Contao\Controller::class));
        $listener = new RegistrationInfoMailerHookListener($db, $sender, $logger, $framework);
        $module = $this->getMockBuilder(Module::class)->disableOriginalConstructor()->onlyMethods(['compile'])->getMock();
        $module->rim_active = '1';
        $module->rim_mailtemplate = 11;
        $module->rim_do_syslog = $enabled ? '1' : '';
        $listener->onCreateNewUser(42, ['firstname'=>'Ada','language'=>'de'], $module);
    }

    public static function loggingFlags(): iterable
    {
        yield 'logging enabled' => [true];
        yield 'logging disabled' => [false];
    }

    public function testGroupLookupUsesContaoGroupsWithoutAssociationTables(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects(self::once())->method('fetchAllAssociative')->willReturn([
            ['id'=>1,'groups'=>serialize(['2','3']),'email'=>'one@example.invalid'],
            ['id'=>2,'groups'=>serialize(['4']),'email'=>'two@example.invalid'],
        ]);
        $result = (new GroupOfMembers($db))->getMembersOfGroupIds([2,3,2,0]);
        self::assertCount(2, $result);
        self::assertSame([2,3], array_column($result, 'group_id'));
        self::assertSame([1,1], array_column($result, 'id'));
    }

    public function testEmptyGroupSelectionDoesNotReadMembers(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects(self::never())->method('fetchAllAssociative');
        self::assertSame([], (new GroupOfMembers($db))->getMembersOfGroupIds([]));
    }
}

class DefaultTemplates
{
    public static function get(string $name): int
    {
        return $name === 'rim_activate_mailtemplate_default' ? 11 : 12;
    }
}
