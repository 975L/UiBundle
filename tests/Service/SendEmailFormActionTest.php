<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Tests\Service;

use c975L\UiBundle\Contract\DebugPreviewCapableInterface;
use c975L\UiBundle\Entity\Form;
use c975L\UiBundle\Entity\FormField;
use c975L\UiBundle\Model\EmailSendRequest;
use c975L\UiBundle\Service\EmailService;
use c975L\UiBundle\Service\SendEmailFormAction;
use PHPUnit\Framework\TestCase;

class SendEmailFormActionTest extends TestCase
{
    public function testImplementsDebugPreviewCapableInterface(): void
    {
        $this->assertInstanceOf(DebugPreviewCapableInterface::class, new SendEmailFormAction($this->createStub(EmailService::class)));
    }

    public function testConsumeDebugPreviewDelegatesToEmailService(): void
    {
        $emailService = $this->createStub(EmailService::class);
        $emailService->method('consumeDebugPreview')->willReturn('<html>preview</html>');

        $action = new SendEmailFormAction($emailService);

        $this->assertSame('<html>preview</html>', $action->consumeDebugPreview());
    }

    private function buildForm(string $name, ?array $actionConfig, array $fields): Form
    {
        $form = new Form();
        $form->setName($name);
        $form->setActionConfig($actionConfig);
        foreach ($fields as $fieldName => $label) {
            $field = new FormField();
            $field->setName($fieldName);
            $field->setLabel($label);
            $form->addField($field);
        }

        return $form;
    }

    public function testGetKeyReturnsSendEmail(): void
    {
        $action = new SendEmailFormAction($this->createStub(EmailService::class));

        $this->assertSame('send_email', $action->getKey());
    }

    public function testHandleUsesDefaultSubjectAndTemplateWhenNoActionConfig(): void
    {
        $captured = null;
        $emailService = $this->createStub(EmailService::class);
        $emailService->method('send')->willReturnCallback(function (EmailSendRequest $request) use (&$captured): bool {
            $captured = $request;

            return true;
        });

        $form = $this->buildForm('newsletter', null, ['email' => 'Email']);
        $action = new SendEmailFormAction($emailService);

        $result = $action->handle($form, ['email' => 'visitor@example.com']);

        $this->assertTrue($result);
        $this->assertSame('New submission: newsletter', $captured->subject);
        $this->assertSame('@c975LUi/emails/form_submission.html.twig', $captured->template);
        $this->assertSame(['Email' => 'visitor@example.com'], $captured->context['fields']);
        $this->assertNull($captured->to);
        $this->assertNull($captured->copyToEmail);
    }

    // Regression guard: only "name" is guaranteed unique per Form - two fields sharing the same label must
    // not collapse onto the same context key and silently drop one of the two submitted values
    public function testHandleDisambiguatesDuplicateFieldLabels(): void
    {
        $captured = null;
        $emailService = $this->createStub(EmailService::class);
        $emailService->method('send')->willReturnCallback(function (EmailSendRequest $request) use (&$captured): bool {
            $captured = $request;

            return true;
        });

        $form = $this->buildForm('contact', null, ['phone' => 'Phone', 'phone-2' => 'Phone']);
        $action = new SendEmailFormAction($emailService);

        $action->handle($form, ['phone' => '0600000000', 'phone-2' => '0700000000']);

        $this->assertSame(
            ['Phone' => '0600000000', 'Phone (2)' => '0700000000'],
            $captured->context['fields']
        );
    }

    public function testHandleUsesActionConfigOverrides(): void
    {
        $captured = null;
        $emailService = $this->createStub(EmailService::class);
        $emailService->method('send')->willReturnCallback(function (EmailSendRequest $request) use (&$captured): bool {
            $captured = $request;

            return true;
        });

        $form = $this->buildForm('newsletter', [
            'to' => 'owner@example.com',
            'toName' => 'Owner',
            'subject' => 'Fixed subject',
            'template' => '@App/emails/custom.html.twig',
            'senderEmailField' => 'email',
            'offerReceiveCopy' => true,
        ], ['email' => 'Email']);
        $action = new SendEmailFormAction($emailService);

        // "receiveCopy" is the visitor's own checkbox answer (see FormSubmissionType), not a fixed admin choice
        $action->handle($form, ['email' => 'visitor@example.com', 'receiveCopy' => true]);

        $this->assertSame('owner@example.com', $captured->to);
        $this->assertSame('Owner', $captured->toName);
        $this->assertSame('Fixed subject', $captured->subject);
        $this->assertSame('@App/emails/custom.html.twig', $captured->template);
        $this->assertSame('visitor@example.com', $captured->replyTo);
        $this->assertSame('visitor@example.com', $captured->copyToEmail);
    }

    public function testHandleDoesNotSendCopyWhenCheckboxNotOffered(): void
    {
        $captured = null;
        $emailService = $this->createStub(EmailService::class);
        $emailService->method('send')->willReturnCallback(function (EmailSendRequest $request) use (&$captured): bool {
            $captured = $request;

            return true;
        });

        $form = $this->buildForm('newsletter', ['senderEmailField' => 'email'], ['email' => 'Email']);
        $action = new SendEmailFormAction($emailService);

        $action->handle($form, ['email' => 'visitor@example.com']);

        $this->assertNull($captured->copyToEmail);
        // senderEmailField still feeds replyTo even without receiveCopy
        $this->assertSame('visitor@example.com', $captured->replyTo);
    }

    public function testHandleDoesNotSendCopyWhenCheckboxOfferedButLeftUnchecked(): void
    {
        $captured = null;
        $emailService = $this->createStub(EmailService::class);
        $emailService->method('send')->willReturnCallback(function (EmailSendRequest $request) use (&$captured): bool {
            $captured = $request;

            return true;
        });

        $form = $this->buildForm('newsletter', ['senderEmailField' => 'email', 'offerReceiveCopy' => true], ['email' => 'Email']);
        $action = new SendEmailFormAction($emailService);

        $action->handle($form, ['email' => 'visitor@example.com', 'receiveCopy' => false]);

        $this->assertNull($captured->copyToEmail);
    }

    public function testHandleReturnsFalseWhenEmailServiceFails(): void
    {
        $emailService = $this->createStub(EmailService::class);
        $emailService->method('send')->willReturn(false);

        $form = $this->buildForm('newsletter', null, []);
        $action = new SendEmailFormAction($emailService);

        $this->assertFalse($action->handle($form, []));
    }
}
