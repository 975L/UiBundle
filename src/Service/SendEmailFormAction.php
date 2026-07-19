<?php
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\UiBundle\Service;

use c975L\UiBundle\Contract\DebugPreviewCapableInterface;
use c975L\UiBundle\Contract\FormActionInterface;
use c975L\UiBundle\Entity\Form;
use c975L\UiBundle\Model\EmailSendRequest;

// Built-in FormActionInterface provider (key "send_email"), so a Form built entirely through the admin - no custom bundle/code - can still notify someone by email on submit. Configured via Form::$actionConfig: "to"/"toName"/"from"/"fromName"/"replyTo"/"replyToName"/"subject"/"template" (all optional, EmailService/ConfigService fill in the rest), "senderEmailField" (name of the submitted field holding the visitor's own email, used as replyTo) and "offerReceiveCopy" (shows a "receive a copy" checkbox, see FormSubmissionType - the visitor's own answer, not a fixed admin choice, decides whether a copy is actually sent)
class SendEmailFormAction implements FormActionInterface, DebugPreviewCapableInterface
{
    private const DEFAULT_TEMPLATE = '@c975LUi/emails/form_submission.html.twig';

    public function __construct(private readonly EmailService $emailService)
    {
    }

    public function getKey(): string
    {
        return 'send_email';
    }

    public function handle(Form $form, array $submittedData): bool
    {
        $config = $form->getActionConfig() ?? [];

        $senderEmail = isset($config['senderEmailField']) ? ($submittedData[$config['senderEmailField']] ?? null) : null;

        $request = new EmailSendRequest(
            subject: $config['subject'] ?? sprintf('New submission: %s', (string) $form->getName()),
            context: ['form' => $form, 'fields' => $this->labelledFields($form, $submittedData)],
            template: $config['template'] ?? self::DEFAULT_TEMPLATE,
            from: $config['from'] ?? null,
            fromName: $config['fromName'] ?? null,
            to: $config['to'] ?? null,
            toName: $config['toName'] ?? null,
            replyTo: $config['replyTo'] ?? $senderEmail,
            replyToName: $config['replyToName'] ?? null,
            // The visitor's own checkbox answer (see FormSubmissionType's "receiveCopy" field, only rendered when actionConfig's "offerReceiveCopy" is set) - not a fixed admin choice
            copyToEmail: (!empty($submittedData['receiveCopy']) && null !== $senderEmail) ? $senderEmail : null,
        );

        return $this->emailService->send($request);
    }

    public function consumeDebugPreview(): ?string
    {
        return $this->emailService->consumeDebugPreview();
    }

    // label => submitted value, in the Form's field order - only "name" is guaranteed unique per Form, so a
    // repeated label (e.g. two fields both titled "Phone") is disambiguated here instead of silently
    // collapsing onto the same array key and losing one of the two submitted values
    private function labelledFields(Form $form, array $submittedData): array
    {
        $labelled = [];
        $labelCounts = [];
        foreach ($form->getFields() as $field) {
            $label = (string) $field->getLabel();
            $labelCounts[$label] = ($labelCounts[$label] ?? 0) + 1;
            $key = $labelCounts[$label] > 1 ? sprintf('%s (%d)', $label, $labelCounts[$label]) : $label;
            $labelled[$key] = $submittedData[$field->getName()] ?? null;
        }

        return $labelled;
    }
}
